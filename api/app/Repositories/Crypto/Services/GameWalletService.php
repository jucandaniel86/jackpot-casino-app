<?php

	namespace App\Repositories\Crypto\Services;

	use App\Repositories\Crypto\Contracts\GameWalletServiceInterface;
	use App\Repositories\Crypto\Contracts\TransactionWriterInterface;
	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Repositories\Crypto\Support\Money;
	use App\Models\BonusGrant;
	use App\Models\BonusGrantEvent;
	use App\Models\Session;
	use App\Models\Wallet;
	use App\Models\WalletBalance;
	use App\Models\WalletLedgerEntry;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Log;

	class GameWalletService implements GameWalletServiceInterface
	{
		public function __construct(
			private WalletLedgerService        $ledger,
			private TransactionWriterInterface $txWriter,
		)
		{
		}

		public function placeBet(Wallet $wallet, Session $session, string $amountBase, int $decimals, array $ctx): void
		{
			$this->guardCtx($ctx);

			DB::transaction(function () use ($wallet, $session, $amountBase, $decimals, $ctx) {
				$scale = max(8, $decimals);
				$requestedUi = isset($ctx['amount_ui']) && (string)$ctx['amount_ui'] !== ''
					? (string)$ctx['amount_ui']
					: Money::baseToUi($amountBase, $decimals, $scale);
				$requestedBase = Money::uiToBase($requestedUi, $decimals);

				$remainingUi = $requestedUi;
				$bonusUsedBase = '0';
				$realUsedBase = '0';
				$bonusWallet = $this->findBonusWalletFor($wallet);
				$policy = $this->resolveRuntimeBonusPolicy($bonusWallet);
				$consumePriority = (string)($policy['consume_priority'] ?? 'bonus_first');

				if ($consumePriority === 'real_first') {
					if (bccomp($remainingUi, '0', $scale) === 1) {
						$realUsedUi = $this->debitRealWalletUi($wallet, $remainingUi, $requestedUi, $requestedBase, $decimals, $bonusWallet, $ctx);
						if (bccomp($realUsedUi, '0', $scale) === 1) {
							$remainingUi = bcsub($remainingUi, $realUsedUi, $scale);
							$realUsedBase = bcadd($realUsedBase, Money::uiToBase($realUsedUi, $decimals), 0);
						}
					}

					if ($bonusWallet && bccomp($remainingUi, '0', $scale) === 1) {
						$bonusUsedUi = $this->debitBonusWalletUi($wallet, $bonusWallet, $remainingUi, $requestedUi, $requestedBase, $scale, $ctx);
						if (bccomp($bonusUsedUi, '0', $scale) === 1) {
							$remainingUi = bcsub($remainingUi, $bonusUsedUi, $scale);
							$bonusUsedBase = bcadd($bonusUsedBase, Money::uiToBase($bonusUsedUi, CurrencyDecimals::internalForWallet($bonusWallet)), 0);
						}
					}
				} else {
					if ($bonusWallet && bccomp($remainingUi, '0', $scale) === 1) {
						$bonusUsedUi = $this->debitBonusWalletUi($wallet, $bonusWallet, $remainingUi, $requestedUi, $requestedBase, $scale, $ctx);
						if (bccomp($bonusUsedUi, '0', $scale) === 1) {
							$remainingUi = bcsub($remainingUi, $bonusUsedUi, $scale);
							$bonusUsedBase = bcadd($bonusUsedBase, Money::uiToBase($bonusUsedUi, CurrencyDecimals::internalForWallet($bonusWallet)), 0);
						}
					}

					if (bccomp($remainingUi, '0', $scale) === 1) {
						$realUsedUi = $this->debitRealWalletUi($wallet, $remainingUi, $requestedUi, $requestedBase, $decimals, $bonusWallet, $ctx);
						if (bccomp($realUsedUi, '0', $scale) === 1) {
							$remainingUi = bcsub($remainingUi, $realUsedUi, $scale);
							$realUsedBase = bcadd($realUsedBase, Money::uiToBase($realUsedUi, $decimals), 0);
						}
					}
				}

				if (bccomp($remainingUi, '0', $scale) === 1) {
					throw new \RuntimeException('INSUFFICIENT_FUNDS');
				}

				if ($bonusWallet && bccomp($realUsedBase, '0', 0) === 1) {
					$this->progressRealWagerOnGrants($wallet, $bonusWallet, $realUsedBase, $ctx);
				}

				// 2) Scrie audit în transactions (optional, dar recomandat)
				$this->txWriter->writeGameTransaction(
					wallet: $wallet,
					type: 'game_bet',
					status: 'confirmed',
					amountBase: $requestedBase,
					decimals: $decimals,
					meta: array_merge($ctx, [
						'bet_split' => [
							'bonus_used_base' => $bonusUsedBase,
							'real_used_base' => $realUsedBase,
							'requested_ui' => $requestedUi,
							'requested_base_real' => $requestedBase,
							'bonus_wallet_id' => $bonusWallet?->id,
							'real_wallet_id' => $wallet->id,
						],
					]),
					session: $session
				);
			});
		}

		public function applyWin(Wallet $wallet, Session $session, string $amountBase, int $decimals, array $ctx): void
		{
			$this->guardCtx($ctx);
			DB::transaction(function () use ($wallet, $session, $amountBase, $decimals, $ctx) {
				$split = $this->resolveRoundStakeSplit($wallet, $ctx);
				$bonusStakeBase = $split['bonus_stake_base'];
				$realStakeBase = $split['real_stake_base'];
				$bonusWallet = $split['bonus_wallet'];
				$policy = $this->resolveRuntimeBonusPolicy($bonusWallet);
				$winDestination = (string)($policy['win_destination'] ?? 'real_wallet');

				$bonusWinBase = '0';
				$realWinBase = $amountBase;

				if ($winDestination === 'bonus_wallet' && $bonusWallet) {
					$totalStakeBase = bcadd($bonusStakeBase, $realStakeBase, 0);
					if (bccomp($totalStakeBase, '0', 0) === 1) {
						$bonusWinBase = bcdiv(bcmul($amountBase, $bonusStakeBase, 0), $totalStakeBase, 0);
						$realWinBase = bcsub($amountBase, $bonusWinBase, 0);
					} else {
						$bonusWinBase = $amountBase;
						$realWinBase = '0';
					}
				}

				if (bccomp($realWinBase, '0', 0) === 1) {
					$this->ledger->creditAvailable(
						wallet: $wallet,
						type: 'win',
						amountBase: $realWinBase,
						decimals: $decimals,
						idempotencyKey: $this->idKey('win', $ctx, 'wallet:' . $wallet->id),
						referenceType: 'provider',
						referenceId: $ctx['provider_tx_id'],
						meta: array_merge($ctx, [
							'win_credit_policy' => $winDestination,
							'bonus_stake_base' => $bonusStakeBase,
							'real_stake_base' => $realStakeBase,
						])
					);
				}

				if ($bonusWallet && bccomp($bonusWinBase, '0', 0) === 1) {
					$bonusDecimals = CurrencyDecimals::internalForWallet($bonusWallet);
					$this->ledger->creditAvailable(
						wallet: $bonusWallet,
						type: 'win_bonus',
						amountBase: $bonusWinBase,
						decimals: $bonusDecimals,
						idempotencyKey: $this->idKey('win_bonus', $ctx, 'wallet:' . $bonusWallet->id),
						referenceType: 'provider',
						referenceId: $ctx['provider_tx_id'],
						meta: array_merge($ctx, [
							'win_credit_policy' => $winDestination,
							'bonus_stake_base' => $bonusStakeBase,
							'real_stake_base' => $realStakeBase,
						])
					);

					$this->creditBonusGrants($bonusWallet, $wallet, $bonusWinBase, $ctx);
				}

				// 2) Audit
				$this->txWriter->writeGameTransaction(
					wallet: $wallet,
					type: 'game_win',
					status: 'confirmed',
					amountBase: $amountBase,
					decimals: $decimals,
					meta: array_merge($ctx, [
						'win_split' => [
							'bonus_win_base' => $bonusWinBase,
							'real_win_base' => $realWinBase,
							'bonus_stake_base' => $bonusStakeBase,
							'real_stake_base' => $realStakeBase,
							'bonus_wallet_id' => $bonusWallet?->id,
							'real_wallet_id' => $wallet->id,
							'policy' => $winDestination,
						],
					]),
					session: $session
				);
			});
		}

		public function refundBet(
			Wallet $wallet,
			string $amountBase,
			int    $decimals,
			array  $ctx,
			string $direction = 'credit'
		): void
		{
			$this->guardCtx($ctx);

			$direction = strtolower($direction);
			if (!in_array($direction, ['credit', 'debit'], true)) {
				throw new \InvalidArgumentException("Invalid refund direction: {$direction}");
			}

			DB::transaction(function () use ($wallet, $amountBase, $decimals, $ctx, $direction) {
				if ($direction === 'credit') {
					// Reverse original bet split (bonus first, then real), based on ledger references.
					$entries = WalletLedgerEntry::query()
						->where('reference_type', 'provider')
						->where('reference_id', (string)$ctx['provider_tx_id'])
						->whereIn('type', ['bet', 'bet_bonus'])
						->where('direction', 'debit')
						->orderBy('id')
						->get();

					if ($entries->isEmpty()) {
						$this->ledger->creditAvailable(
							wallet: $wallet,
							type: 'refund',
							amountBase: $amountBase,
							decimals: $decimals,
							idempotencyKey: $this->idKey('refund_credit', $ctx, 'wallet:' . $wallet->id),
							referenceType: 'provider',
							referenceId: $ctx['provider_tx_id'],
							meta: $ctx
						);
					} else {
						foreach ($entries as $entry) {
							$deltaAvail = (string)($entry->meta['delta_available_base'] ?? '0');
							$creditBase = ltrim($deltaAvail, '-');
							if (bccomp($creditBase, '0', 0) <= 0) {
								continue;
							}

							$targetWallet = Wallet::query()->find($entry->wallet_id);
							if (!$targetWallet) {
								continue;
							}

							$this->ledger->creditAvailable(
								wallet: $targetWallet,
								type: 'refund',
								amountBase: $creditBase,
								decimals: $decimals,
								idempotencyKey: $this->idKey('refund_credit', $ctx, 'wallet:' . $targetWallet->id),
								referenceType: 'provider',
								referenceId: $ctx['provider_tx_id'],
								meta: array_merge($ctx, [
									'refunded_from_ledger_entry_id' => $entry->id,
									'refunded_original_wallet_id' => $entry->wallet_id,
								])
							);
						}
					}
				} else {
					// Reverse win split from original win ledger entries.
					$entries = WalletLedgerEntry::query()
						->where('reference_type', 'provider')
						->where('reference_id', (string)$ctx['provider_tx_id'])
						->whereIn('type', ['win', 'win_bonus'])
						->where('direction', 'credit')
						->orderBy('id')
						->get();

					if ($entries->isEmpty()) {
						$this->ledger->debitAvailable(
							wallet: $wallet,
							type: 'refund',
							amountBase: $amountBase,
							decimals: $decimals,
							idempotencyKey: $this->idKey('refund_debit', $ctx, 'wallet:' . $wallet->id),
							referenceType: 'provider',
							referenceId: $ctx['provider_tx_id'],
							meta: $ctx
						);
					} else {
						foreach ($entries as $entry) {
							$deltaAvail = (string)($entry->meta['delta_available_base'] ?? '0');
							$debitBase = ltrim($deltaAvail, '+');
							if (bccomp($debitBase, '0', 0) <= 0) {
								continue;
							}

							$targetWallet = Wallet::query()->find($entry->wallet_id);
							if (!$targetWallet) {
								continue;
							}

							$this->ledger->debitAvailable(
								wallet: $targetWallet,
								type: 'refund',
								amountBase: $debitBase,
								decimals: $decimals,
								idempotencyKey: $this->idKey('refund_debit', $ctx, 'wallet:' . $targetWallet->id),
								referenceType: 'provider',
								referenceId: $ctx['provider_tx_id'],
								meta: array_merge($ctx, [
									'refunded_from_ledger_entry_id' => $entry->id,
									'refunded_original_wallet_id' => $entry->wallet_id,
								])
							);
						}
					}
				}

				$session = Session::query()
					->where('session', (string)($ctx['session_id'] ?? ''))
					->first();

				if ($session) {
					$this->txWriter->writeGameTransaction(
						wallet: $wallet,
						session: $session,
						type: 'game_refund',
						status: 'confirmed',
						amountBase: $amountBase,
						decimals: $decimals,
						meta: $ctx
					);
				}
			});
		}

		private function guardCtx(array $ctx): void
		{
			foreach (['provider', 'provider_tx_id', 'round_id'] as $k) {
				if (empty($ctx[$k])) {
					throw new \InvalidArgumentException("Missing ctx[$k]");
				}
			}
		}

		private function idKey(string $action, array $ctx, ?string $suffix = null): string
		{
			$key = "game:{$ctx['provider']}:{$action}:{$ctx['provider_tx_id']}";
			return $suffix ? "{$key}:{$suffix}" : $key;
		}

		private function findBonusWalletFor(Wallet $realWallet): ?Wallet
		{
			return Wallet::query()
				->where('holder_type', $realWallet->holder_type)
				->where('holder_id', $realWallet->holder_id)
				->where('currency_id', $realWallet->currency_id)
				->where('id', '!=', $realWallet->id)
				->whereHas('type', function ($q) {
					$q->where('purpose', 'bonus')->where('active', 1);
				})
				->first();
		}

		private function minBase(string $a, string $b): string
		{
			return bccomp($a, $b, 0) <= 0 ? $a : $b;
		}

		private function minUi(string $a, string $b, int $scale): string
		{
			return bccomp($a, $b, $scale) <= 0 ? $a : $b;
		}

		private function resolveRoundStakeSplit(Wallet $realWallet, array $ctx): array
		{
			$bonusWallet = $this->findBonusWalletFor($realWallet);
			$walletIds = [$realWallet->id];
			if ($bonusWallet) {
				$walletIds[] = $bonusWallet->id;
			}

			$entries = WalletLedgerEntry::query()
				->whereIn('wallet_id', $walletIds)
				->where('reference_type', 'provider')
				->where('type', 'like', 'bet%')
				->where('direction', 'debit')
				->where('meta->provider', (string)$ctx['provider'])
				->where('meta->round_id', (string)$ctx['round_id'])
				->get();

			$realStakeBase = '0';
			$bonusStakeBase = '0';

			foreach ($entries as $entry) {
				$deltaAvail = (string)($entry->meta['delta_available_base'] ?? '0');
				$stakeBase = ltrim($deltaAvail, '-');
				if (bccomp($stakeBase, '0', 0) <= 0) {
					continue;
				}

				if ((int)$entry->wallet_id === (int)$realWallet->id) {
					$realStakeBase = bcadd($realStakeBase, $stakeBase, 0);
					continue;
				}

				if ($bonusWallet && (int)$entry->wallet_id === (int)$bonusWallet->id) {
					$bonusStakeBase = bcadd($bonusStakeBase, $stakeBase, 0);
				}
			}

			return [
				'bonus_wallet' => $bonusWallet,
				'bonus_stake_base' => $bonusStakeBase,
				'real_stake_base' => $realStakeBase,
			];
		}

		private function consumeBonusGrants(Wallet $bonusWallet, string $consumeBase, array $ctx): void
		{
			$remainingToConsume = $consumeBase;
			if (bccomp($remainingToConsume, '0', 0) <= 0) {
				return;
			}

			$grants = BonusGrant::query()
				->where('wallet_id_bonus', $bonusWallet->id)
				->whereIn('status', ['active', 'granted'])
				->where('amount_remaining_base', '>', 0)
				->orderByRaw('expires_at IS NULL')
				->orderBy('expires_at')
				->orderBy('id')
				->lockForUpdate()
				->get();

			foreach ($grants as $grant) {
				if (bccomp($remainingToConsume, '0', 0) <= 0) {
					break;
				}

				$grantRemainingBase = (string)$grant->amount_remaining_base;
				$takeBase = $this->minBase($grantRemainingBase, $remainingToConsume);
				if (bccomp($takeBase, '0', 0) <= 0) {
					continue;
				}

				$eventIdKey = $this->idKey('bonus_consume', $ctx, 'grant:' . $grant->id);
				$alreadyApplied = BonusGrantEvent::query()
					->where('idempotency_key', $eventIdKey)
					->exists();
				if ($alreadyApplied) {
					$remainingToConsume = bcsub($remainingToConsume, $takeBase, 0);
					continue;
				}

				$newRemainingBase = bcsub($grantRemainingBase, $takeBase, 0);
				$grant->amount_remaining_base = $newRemainingBase;
				$grant->wagering_progress_base = bcadd((string)$grant->wagering_progress_base, $takeBase, 0);
				$grant->status = bccomp($newRemainingBase, '0', 0) === 0 ? 'consumed' : 'active';
				$grant->save();

				BonusGrantEvent::query()->create([
					'bonus_grant_id' => $grant->id,
					'event_type' => 'bet_debit',
					'amount_base' => $takeBase,
					'idempotency_key' => $eventIdKey,
					'reference_type' => 'provider',
					'reference_id' => (string)($ctx['provider_tx_id'] ?? ''),
					'meta' => [
						'provider' => (string)($ctx['provider'] ?? ''),
						'round_id' => (string)($ctx['round_id'] ?? ''),
						'game_id' => (string)($ctx['game_id'] ?? ''),
						'session_id' => (string)($ctx['session_id'] ?? ''),
						'wallet_id_bonus' => $bonusWallet->id,
					],
				]);
				$this->evaluateGrantLifecycle($grant, $bonusWallet, $ctx);

				$remainingToConsume = bcsub($remainingToConsume, $takeBase, 0);
			}

			if (bccomp($remainingToConsume, '0', 0) === 1) {
				Log::warning('bonus.consume.unallocated', [
					'wallet_id_bonus' => $bonusWallet->id,
					'requested_base' => $consumeBase,
					'unallocated_base' => $remainingToConsume,
					'ctx' => $ctx,
				]);
			}
		}

		private function debitRealWalletUi(
			Wallet $realWallet,
			string $remainingUi,
			string $requestedUi,
			string $requestedBase,
			int $realDecimals,
			?Wallet $bonusWallet,
			array $ctx
		): string
		{
			$scale = max(8, $realDecimals);
			$realAvailableUi = $this->ledger->getAvailableUi($realWallet, $scale);
			if (bccomp($realAvailableUi, '0', $scale) <= 0) {
				return '0';
			}

			$realUsedUi = $this->minUi($realAvailableUi, $remainingUi, $scale);
			$realUsedBase = Money::uiToBase($realUsedUi, $realDecimals);
			if (bccomp($realUsedBase, '0', 0) <= 0) {
				return '0';
			}

			$this->ledger->debitAvailable(
				wallet: $realWallet,
				type: 'bet',
				amountBase: $realUsedBase,
				decimals: $realDecimals,
				idempotencyKey: $this->idKey('bet', $ctx, 'wallet:' . $realWallet->id),
				referenceType: 'provider',
				referenceId: $ctx['provider_tx_id'],
				meta: array_merge($ctx, [
					'bet_source' => 'real',
					'bonus_wallet_id' => $bonusWallet?->id,
					'bet_amount_ui' => $requestedUi,
					'bet_amount_base_real' => $requestedBase,
					'real_used_ui' => $realUsedUi,
				])
			);

			return $realUsedUi;
		}

		private function debitBonusWalletUi(
			Wallet $realWallet,
			Wallet $bonusWallet,
			string $remainingUi,
			string $requestedUi,
			string $requestedBase,
			int $scale,
			array $ctx
		): string
		{
			$bonusDecimals = CurrencyDecimals::internalForWallet($bonusWallet);
			$bonusBalance = WalletBalance::query()
				->where('wallet_id', $bonusWallet->id)
				->lockForUpdate()
				->first();

			$bonusAvailableBase = (string)($bonusBalance?->available_base ?? '0');
			if (bccomp($bonusAvailableBase, '0', 0) <= 0) {
				return '0';
			}

			$bonusAvailableUi = Money::baseToUi($bonusAvailableBase, $bonusDecimals, $scale);
			$bonusUsedUi = $this->minUi($bonusAvailableUi, $remainingUi, $scale);
			$bonusUsedBase = Money::uiToBase($bonusUsedUi, $bonusDecimals);
			if (bccomp($bonusUsedBase, '0', 0) <= 0) {
				return '0';
			}

			$applied = $this->ledger->debitAvailable(
				wallet: $bonusWallet,
				type: 'bet_bonus',
				amountBase: $bonusUsedBase,
				decimals: $bonusDecimals,
				idempotencyKey: $this->idKey('bet_bonus', $ctx, 'wallet:' . $bonusWallet->id),
				referenceType: 'provider',
				referenceId: $ctx['provider_tx_id'],
				meta: array_merge($ctx, [
					'bet_source' => 'bonus',
					'linked_real_wallet_id' => $realWallet->id,
					'bet_amount_ui' => $requestedUi,
					'bet_amount_base_real' => $requestedBase,
					'bonus_used_ui' => $bonusUsedUi,
				])
			);

			if (!$applied) {
				return '0';
			}

			$this->consumeBonusGrants($bonusWallet, $bonusUsedBase, $ctx);
			return $bonusUsedUi;
		}

		private function progressRealWagerOnGrants(Wallet $realWallet, Wallet $bonusWallet, string $consumeBase, array $ctx): void
		{
			$remaining = $consumeBase;
			if (bccomp($remaining, '0', 0) <= 0) {
				return;
			}

			$grants = BonusGrant::query()
				->where('wallet_id_bonus', $bonusWallet->id)
				->whereIn('status', ['active', 'granted', 'consumed'])
				->whereRaw('real_wager_required_base > real_wager_progress_base')
				->orderByRaw('expires_at IS NULL')
				->orderBy('expires_at')
				->orderBy('id')
				->lockForUpdate()
				->get();

			foreach ($grants as $grant) {
				if (bccomp($remaining, '0', 0) <= 0) {
					break;
				}

				$required = (string)($grant->real_wager_required_base ?? '0');
				$progress = (string)($grant->real_wager_progress_base ?? '0');
				$left = bcsub($required, $progress, 0);
				if (bccomp($left, '0', 0) <= 0) {
					continue;
				}

				$take = $this->minBase($left, $remaining);
				$idempotencyKey = $this->idKey('real_wager_progress', $ctx, 'grant:' . $grant->id);
				if (BonusGrantEvent::query()->where('idempotency_key', $idempotencyKey)->exists()) {
					$remaining = bcsub($remaining, $take, 0);
					continue;
				}

				$grant->real_wager_progress_base = bcadd($progress, $take, 0);
				$grant->save();

				BonusGrantEvent::query()->create([
					'bonus_grant_id' => $grant->id,
					'event_type' => 'real_wager_progress',
					'amount_base' => $take,
					'idempotency_key' => $idempotencyKey,
					'reference_type' => 'provider',
					'reference_id' => (string)($ctx['provider_tx_id'] ?? ''),
					'meta' => [
						'provider' => (string)($ctx['provider'] ?? ''),
						'round_id' => (string)($ctx['round_id'] ?? ''),
						'wallet_id_real' => $realWallet->id,
					],
				]);

				$this->evaluateGrantLifecycle($grant, $bonusWallet, $ctx, $realWallet);
				$remaining = bcsub($remaining, $take, 0);
			}
		}

		private function creditBonusGrants(Wallet $bonusWallet, Wallet $realWallet, string $creditBase, array $ctx): void
		{
			$remaining = $creditBase;
			if (bccomp($remaining, '0', 0) <= 0) {
				return;
			}

			$grants = BonusGrant::query()
				->where('wallet_id_bonus', $bonusWallet->id)
				->whereIn('status', ['active', 'granted', 'consumed'])
				->orderByRaw('expires_at IS NULL')
				->orderBy('expires_at')
				->orderBy('id')
				->lockForUpdate()
				->get();

			if ($grants->isEmpty()) {
				return;
			}

			$leftGrants = $grants->count();
			foreach ($grants as $grant) {
				if (bccomp($remaining, '0', 0) <= 0) {
					break;
				}

				$allocation = $leftGrants === 1 ? $remaining : bcdiv($remaining, (string)$leftGrants, 0);
				if (bccomp($allocation, '0', 0) <= 0) {
					$leftGrants--;
					continue;
				}

				$idempotencyKey = $this->idKey('bonus_win_credit', $ctx, 'grant:' . $grant->id);
				if (BonusGrantEvent::query()->where('idempotency_key', $idempotencyKey)->exists()) {
					$remaining = bcsub($remaining, $allocation, 0);
					$leftGrants--;
					continue;
				}

				$grant->amount_remaining_base = bcadd((string)$grant->amount_remaining_base, $allocation, 0);
				$grant->status = 'active';
				$grant->save();

				BonusGrantEvent::query()->create([
					'bonus_grant_id' => $grant->id,
					'event_type' => 'win_credit',
					'amount_base' => $allocation,
					'idempotency_key' => $idempotencyKey,
					'reference_type' => 'provider',
					'reference_id' => (string)($ctx['provider_tx_id'] ?? ''),
					'meta' => [
						'provider' => (string)($ctx['provider'] ?? ''),
						'round_id' => (string)($ctx['round_id'] ?? ''),
						'wallet_id_bonus' => $bonusWallet->id,
					],
				]);

				$this->evaluateGrantLifecycle($grant, $bonusWallet, $ctx, $realWallet);
				$remaining = bcsub($remaining, $allocation, 0);
				$leftGrants--;
			}
		}

		private function evaluateGrantLifecycle(
			BonusGrant $grant,
			Wallet $bonusWallet,
			array $ctx,
			?Wallet $realWallet = null
		): void
		{
			$bonusRequired = (string)($grant->wagering_required_base ?? '0');
			$bonusProgress = (string)($grant->wagering_progress_base ?? '0');
			$realRequired = (string)($grant->real_wager_required_base ?? '0');
			$realProgress = (string)($grant->real_wager_progress_base ?? '0');

			$bonusDone = bccomp($bonusRequired, '0', 0) <= 0 || bccomp($bonusProgress, $bonusRequired, 0) >= 0;
			$realDone = bccomp($realRequired, '0', 0) <= 0 || bccomp($realProgress, $realRequired, 0) >= 0;
			if (!$bonusDone || !$realDone) {
				return;
			}

			$wageringEventKey = "bonus:wagering_completed:grant:{$grant->id}";
			if (!BonusGrantEvent::query()->where('idempotency_key', $wageringEventKey)->exists()) {
				BonusGrantEvent::query()->create([
					'bonus_grant_id' => $grant->id,
					'event_type' => 'wagering_completed',
					'amount_base' => '0',
					'idempotency_key' => $wageringEventKey,
					'reference_type' => 'grant',
					'reference_id' => (string)$grant->id,
					'meta' => [
						'wallet_id_bonus' => $bonusWallet->id,
					],
				]);
			}

			if ((int)$grant->withdraw_lock === 1) {
				$grant->withdraw_lock = 0;
				$grant->save();
			}

			$maxConvertBase = (string)($grant->max_convert_to_real_base ?? '0');
			$convertedBase = (string)($grant->converted_to_real_base ?? '0');
			$remainingBase = (string)($grant->amount_remaining_base ?? '0');

			$capLeft = bccomp($maxConvertBase, '0', 0) === 1
				? bcsub($maxConvertBase, $convertedBase, 0)
				: $remainingBase;

			if (bccomp($capLeft, '0', 0) <= 0 || bccomp($remainingBase, '0', 0) <= 0) {
				return;
			}

			$convertBase = $this->minBase($remainingBase, $capLeft);
			if (bccomp($convertBase, '0', 0) <= 0) {
				return;
			}

			$realWallet = $realWallet ?: $this->findRealWalletForBonus($bonusWallet);
			if (!$realWallet) {
				return;
			}

			$bonusDecimals = CurrencyDecimals::internalForWallet($bonusWallet);
			$realDecimals = CurrencyDecimals::internalForWallet($realWallet);
			$convertKey = $this->idKey('bonus_convert', $ctx, 'grant:' . $grant->id);

			if (BonusGrantEvent::query()->where('idempotency_key', $convertKey)->exists()) {
				return;
			}

			$this->ledger->debitAvailable(
				wallet: $bonusWallet,
				type: 'bonus_convert_out',
				amountBase: $convertBase,
				decimals: $bonusDecimals,
				idempotencyKey: $convertKey . ':out',
				referenceType: 'bonus_grant',
				referenceId: (string)$grant->id,
				meta: array_merge($ctx, ['bonus_grant_id' => $grant->id])
			);

			$this->ledger->creditAvailable(
				wallet: $realWallet,
				type: 'bonus_convert_in',
				amountBase: $convertBase,
				decimals: $realDecimals,
				idempotencyKey: $convertKey . ':in',
				referenceType: 'bonus_grant',
				referenceId: (string)$grant->id,
				meta: array_merge($ctx, ['bonus_grant_id' => $grant->id])
			);

			$grant->amount_remaining_base = bcsub((string)$grant->amount_remaining_base, $convertBase, 0);
			$grant->converted_to_real_base = bcadd((string)$grant->converted_to_real_base, $convertBase, 0);
			$grant->save();

			BonusGrantEvent::query()->create([
				'bonus_grant_id' => $grant->id,
				'event_type' => 'conversion_to_real',
				'amount_base' => $convertBase,
				'idempotency_key' => $convertKey,
				'reference_type' => 'bonus_grant',
				'reference_id' => (string)$grant->id,
				'meta' => [
					'wallet_id_bonus' => $bonusWallet->id,
					'wallet_id_real' => $realWallet->id,
				],
			]);
		}

		private function findRealWalletForBonus(Wallet $bonusWallet): ?Wallet
		{
			return Wallet::query()
				->where('holder_type', $bonusWallet->holder_type)
				->where('holder_id', $bonusWallet->holder_id)
				->where('currency_id', $bonusWallet->currency_id)
				->where('id', '!=', $bonusWallet->id)
				->whereHas('type', function ($q) {
					$q->where('purpose', 'real')->where('active', 1);
				})
				->first();
		}

		private function resolveRuntimeBonusPolicy(?Wallet $bonusWallet): array
		{
			if (!$bonusWallet) {
				return [
					'consume_priority' => 'bonus_first',
					'win_destination' => 'real_wallet',
				];
			}

			$grant = BonusGrant::query()
				->leftJoin('bonus_rules', 'bonus_rules.id', '=', 'bonus_grants.bonus_rule_id')
				->where('bonus_grants.wallet_id_bonus', $bonusWallet->id)
				->whereNotNull('bonus_grants.bonus_rule_id')
				->orderByDesc('bonus_grants.id')
				->select([
					'bonus_rules.consume_priority as consume_priority',
					'bonus_rules.win_destination as win_destination',
				])
				->first();

			$consumePriority = (string)($grant?->consume_priority ?? 'bonus_first');
			if (!in_array($consumePriority, ['real_first', 'bonus_first'], true)) {
				$consumePriority = 'bonus_first';
			}

			$winDestination = (string)($grant?->win_destination ?? 'real_wallet');
			if (!in_array($winDestination, ['bonus_wallet', 'real_wallet'], true)) {
				$winDestination = 'real_wallet';
			}

			return [
				'consume_priority' => $consumePriority,
				'win_destination' => $winDestination,
			];
		}
	}
