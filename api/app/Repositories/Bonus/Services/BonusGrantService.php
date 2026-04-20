<?php

namespace App\Repositories\Bonus\Services;

use App\Repositories\Crypto\Services\WalletLedgerService;
use App\Repositories\Crypto\Support\CurrencyDecimals;
use App\Repositories\Crypto\Support\Money;
use App\Models\BonusGrant;
use App\Models\BonusGrantEvent;
use App\Models\BonusRule;
use App\Models\Player;
use App\Models\Wallet;
use App\Models\WalletType;
use App\Notifications\BonusActivatedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BonusGrantService
{
	public function __construct(
		private WalletLedgerService $ledger
	)
	{
	}

	public function grantRegisterBonuses(Player $player): int
	{
		$rules = BonusRule::query()
			->where('is_active', 1)
			->where('trigger_type', 'register')
			->where('int_casino_id', (string)$player->int_casino_id)
			->where(function ($q) {
				$q->whereNull('valid_from')->orWhere('valid_from', '<=', now());
			})
			->where(function ($q) {
				$q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
			})
			->orderBy('priority')
			->orderBy('id')
			->get();

		if ($rules->isEmpty()) {
			return 0;
		}

		$granted = 0;
		foreach ($rules as $rule) {
			if (!$this->isRegisterConditionMet($rule, $player)) {
				continue;
			}

			$alreadyGranted = BonusGrant::query()
				->where('player_id', $player->id)
				->where('bonus_rule_id', $rule->id)
				->exists();

			if ($alreadyGranted) {
				continue;
			}

			$grant = $this->grantRuleToPlayer(
				player: $player,
				rule: $rule,
				sourceType: 'automatic',
				sourceRef: 'register',
				extraMeta: ['trigger' => 'register']
			);
			if ($grant) {
				$granted++;
			}

			if ((string)$rule->stacking_policy === 'exclusive') {
				break;
			}
		}

		return $granted;
	}

	public function grantFirstDepositBonuses(
		Player $player,
		?string $depositAmountUi = null,
		?string $sourceRef = null,
		array $extraMeta = []
	): int
	{
		$rules = BonusRule::query()
			->where('is_active', 1)
			->where('trigger_type', 'first_deposit')
			->where('int_casino_id', (string)$player->int_casino_id)
			->where(function ($q) {
				$q->whereNull('valid_from')->orWhere('valid_from', '<=', now());
			})
			->where(function ($q) {
				$q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
			})
			->orderBy('priority')
			->orderBy('id')
			->get();

		if ($rules->isEmpty()) {
			return 0;
		}

		$granted = 0;
		foreach ($rules as $rule) {
			if (!$this->isFirstDepositConditionMet($rule, $depositAmountUi)) {
				continue;
			}

			$alreadyGranted = BonusGrant::query()
				->where('player_id', $player->id)
				->where('bonus_rule_id', $rule->id)
				->exists();

			if ($alreadyGranted) {
				continue;
			}

			$grant = $this->grantRuleToPlayer(
				player: $player,
				rule: $rule,
				sourceType: 'automatic',
				sourceRef: $sourceRef ?: 'first_deposit',
				extraMeta: array_merge(['trigger' => 'first_deposit'], $extraMeta)
			);

			if ($grant) {
				$granted++;
			}

			if ((string)$rule->stacking_policy === 'exclusive') {
				break;
			}
		}

		return $granted;
	}

	public function grantDepositBonuses(
		Player $player,
		?string $depositAmountUi = null,
		?string $sourceRef = null,
		array $extraMeta = []
	): int
	{
		$rules = BonusRule::query()
			->where('is_active', 1)
			->where('trigger_type', 'deposit')
			->where('int_casino_id', (string)$player->int_casino_id)
			->where(function ($q) {
				$q->whereNull('valid_from')->orWhere('valid_from', '<=', now());
			})
			->where(function ($q) {
				$q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
			})
			->orderBy('priority')
			->orderBy('id')
			->get();

		if ($rules->isEmpty()) {
			return 0;
		}

		$granted = 0;
		foreach ($rules as $rule) {
			if (!$this->isDepositConditionMet($rule, $depositAmountUi)) {
				continue;
			}

			// For recurring deposit campaigns, enforce idempotency per deposit tx/sourceRef.
			$alreadyGranted = BonusGrant::query()
				->where('player_id', $player->id)
				->where('bonus_rule_id', $rule->id)
				->when($sourceRef !== null && trim($sourceRef) !== '', function ($q) use ($sourceRef) {
					$q->where('source_ref', $sourceRef);
				})
				->exists();

			if ($alreadyGranted) {
				continue;
			}

			$grant = $this->grantRuleToPlayer(
				player: $player,
				rule: $rule,
				sourceType: 'automatic',
				sourceRef: $sourceRef ?: 'deposit',
				extraMeta: array_merge(['trigger' => 'deposit'], $extraMeta)
			);

			if ($grant) {
				$granted++;
			}

			if ((string)$rule->stacking_policy === 'exclusive') {
				break;
			}
		}

		return $granted;
	}

	private function isFirstDepositConditionMet(BonusRule $rule, ?string $depositAmountUi): bool
	{
		return $this->isDepositAmountConditionMet($rule, $depositAmountUi);
	}

	private function isDepositConditionMet(BonusRule $rule, ?string $depositAmountUi): bool
	{
		return $this->isDepositAmountConditionMet($rule, $depositAmountUi);
	}

	private function isDepositAmountConditionMet(BonusRule $rule, ?string $depositAmountUi): bool
	{
		$condition = (array)($rule->condition_json ?? []);
		if ($condition === []) {
			return true;
		}

		$minUi = null;

		// Accept both flat and nested forms:
		// {"min_deposit_ui":"100"} OR {"first_deposit":{"min_deposit_ui":"100"}}
		if (array_key_exists('min_deposit_ui', $condition)) {
			$minUi = (string)$condition['min_deposit_ui'];
		} elseif (array_key_exists('min_amount_ui', $condition)) {
			$minUi = (string)$condition['min_amount_ui'];
		} elseif (isset($condition['first_deposit']) && is_array($condition['first_deposit'])) {
			$nested = $condition['first_deposit'];
			if (array_key_exists('min_deposit_ui', $nested)) {
				$minUi = (string)$nested['min_deposit_ui'];
			} elseif (array_key_exists('min_amount_ui', $nested)) {
				$minUi = (string)$nested['min_amount_ui'];
			}
		}

		if ($minUi === null || trim($minUi) === '') {
			return true;
		}

		$depositUi = trim((string)$depositAmountUi);
		if ($depositUi === '') {
			return false;
		}

		return bccomp($depositUi, $minUi, 8) >= 0;
	}

	public function grantRuleToPlayer(
		Player $player,
		BonusRule $rule,
		string $sourceType = 'automatic',
		?string $sourceRef = null,
		array $extraMeta = []
	): ?BonusGrant
	{
		$result = $this->tryGrantRuleToPlayer($player, $rule, $sourceType, $sourceRef, $extraMeta);
		return $result['grant'];
	}

	public function tryGrantRuleToPlayer(
		Player $player,
		BonusRule $rule,
		string $sourceType = 'automatic',
		?string $sourceRef = null,
		array $extraMeta = []
	): array
	{
		$wallet = $this->resolveOrCreateBonusWallet($player, $rule->currency_id ?: config('crypto.defaultCurrency'));
		if (!$wallet) {
			Log::warning('bonus.grant.skip.no_bonus_wallet', [
				'player_id' => $player->id,
				'rule_id' => $rule->id,
			]);
			return ['grant' => null, 'skip_reason' => 'missing_bonus_wallet_type_or_wallet'];
		}

		$decimals = CurrencyDecimals::internalForWallet($wallet);
		$depositAmountBase = isset($extraMeta['deposit_amount_base']) ? (string)$extraMeta['deposit_amount_base'] : null;
		$amountBase = $this->resolveRewardAmountBase($rule, $decimals, $depositAmountBase);
		if (bccomp($amountBase, '0', 0) <= 0) {
			return ['grant' => null, 'skip_reason' => 'zero_or_invalid_reward_amount'];
		}

		$wageringRequiredBase = bcmul($amountBase, (string)max(0, (int)$rule->wagering_multiplier), 0);
		$bonusWagerMultiplier = isset($rule->bonus_wager_multiplier)
			? (int)$rule->bonus_wager_multiplier
			: (int)$rule->wagering_multiplier;
		$bonusWageringRequiredBase = bcmul($amountBase, (string)max(0, $bonusWagerMultiplier), 0);

		$realWagerMultiplier = isset($rule->real_wager_multiplier)
			? (int)$rule->real_wager_multiplier
			: 0;
		$realWagerSourceBase = isset($extraMeta['deposit_amount_base'])
			? (string)$extraMeta['deposit_amount_base']
			: $amountBase;
		$realWagerRequiredBase = bcmul($realWagerSourceBase, (string)max(0, $realWagerMultiplier), 0);

		$maxConvertToRealBase = '0';
		if ($rule->max_convert_to_real_ui !== null) {
			$maxConvertToRealBase = Money::uiToBase((string)$rule->max_convert_to_real_ui, $decimals);
		}

		$withdrawLock = bccomp($bonusWageringRequiredBase, '0', 0) === 1 || bccomp($realWagerRequiredBase, '0', 0) === 1;

		$grant = $this->createGrantAndCredit(
			player: $player,
			wallet: $wallet,
			decimals: $decimals,
			amountBase: $amountBase,
			bonusRuleId: $rule->id,
			sourceType: $sourceType,
			sourceRef: $sourceRef,
			expiresAt: $rule->valid_until,
			wageringRequiredBase: $bonusWageringRequiredBase,
			realWagerRequiredBase: $realWagerRequiredBase,
			maxConvertToRealBase: $maxConvertToRealBase,
			withdrawLock: $withdrawLock,
			meta: array_merge(['rule_name' => $rule->name], $extraMeta)
		);
		return ['grant' => $grant, 'skip_reason' => null];
	}

	public function grantManualToPlayer(
		Player $player,
		string $currencyId,
		string $amountUi,
		int $wageringMultiplier = 0,
		?string $expiresAt = null,
		?string $sourceRef = null,
		array $meta = []
	): ?BonusGrant
	{
		$result = $this->tryGrantManualToPlayer(
			player: $player,
			currencyId: $currencyId,
			amountUi: $amountUi,
			wageringMultiplier: $wageringMultiplier,
			expiresAt: $expiresAt,
			sourceRef: $sourceRef,
			meta: $meta
		);
		return $result['grant'];
	}

	public function tryGrantManualToPlayer(
		Player $player,
		string $currencyId,
		string $amountUi,
		int $wageringMultiplier = 0,
		?string $expiresAt = null,
		?string $sourceRef = null,
		array $meta = []
	): array
	{
		$wallet = $this->resolveOrCreateBonusWallet($player, $currencyId);
		if (!$wallet) {
			return ['grant' => null, 'skip_reason' => 'missing_bonus_wallet_type_or_wallet'];
		}

		$decimals = CurrencyDecimals::internalForWallet($wallet);
		$amountBase = Money::uiToBase((string)$amountUi, $decimals);
		if (bccomp($amountBase, '0', 0) <= 0) {
			return ['grant' => null, 'skip_reason' => 'zero_or_invalid_amount_ui'];
		}

		$wageringRequiredBase = bcmul($amountBase, (string)max(0, $wageringMultiplier), 0);
		$withdrawLock = bccomp($wageringRequiredBase, '0', 0) === 1;

		$grant = $this->createGrantAndCredit(
			player: $player,
			wallet: $wallet,
			decimals: $decimals,
			amountBase: $amountBase,
			bonusRuleId: null,
			sourceType: 'manual_segment',
			sourceRef: $sourceRef,
			expiresAt: $expiresAt,
			wageringRequiredBase: $wageringRequiredBase,
			realWagerRequiredBase: '0',
			maxConvertToRealBase: '0',
			withdrawLock: $withdrawLock,
			meta: $meta
		);
		return ['grant' => $grant, 'skip_reason' => null];
	}

	private function isRegisterConditionMet(BonusRule $rule, Player $player): bool
	{
		$condition = (array)($rule->condition_json ?? []);
		if ($condition === []) {
			return true;
		}

		if (array_key_exists('register', $condition)) {
			return (bool)$condition['register'] === true;
		}

		// For register flow, unknown condition keys are treated as non-eligible.
		Log::info('bonus.register.unknown_condition', [
			'player_id' => $player->id,
			'rule_id' => $rule->id,
			'condition' => $condition,
		]);

		return false;
	}

	private function resolveOrCreateBonusWallet(Player $player, string $currencyId): ?Wallet
	{
		$bonusType = $this->findBonusWalletType($currencyId);

		if (!$bonusType) {
			return null;
		}

		$wallet = Wallet::query()
			->where('holder_type', $player->getMorphClass())
			->where('holder_id', $player->id)
			->where('wallet_type_id', $bonusType->id)
			->first();

		if ($wallet) {
			$this->ledger->ensureBalanceRow($wallet);
			return $wallet;
		}

		$wallet = Wallet::query()->create([
			'holder_type' => $player->getMorphClass(),
			'holder_id' => $player->id,
			'name' => $player->fixed_id . '_' . ($bonusType->currency_code ?: $bonusType->code) . '_bonus',
			'wallet_type_id' => $bonusType->id,
			'uuid' => (string)Str::uuid(),
			'meta' => [
				'purpose' => 'bonus',
				'decimals' => CurrencyDecimals::internalForCurrency((string)$bonusType->currency_id),
			],
			'balance' => 0,
			'currency' => $bonusType->currency_id,
			'currency_id' => $bonusType->currency_id,
			'currency_code' => $bonusType->currency_code ?: $bonusType->code,
			'network' => $bonusType->network,
		]);

		$this->ledger->ensureBalanceRow($wallet);
		return $wallet;
	}

	private function findBonusWalletType(string $currencyId): ?WalletType
	{
		$currencyId = trim((string)$currencyId);
		if ($currencyId === '') {
			return null;
		}

		$candidates = array_values(array_unique(array_filter([
			$currencyId,
			str_ends_with($currencyId, '-BONUS') ? substr($currencyId, 0, -6) : null,
			str_ends_with($currencyId, '-BONUS') ? null : ($currencyId . '-BONUS'),
		])));

		$base = WalletType::query()
			->where('active', 1)
			->where('purpose', 'bonus');

		$byCurrencyId = (clone $base)
			->whereIn('currency_id', $candidates)
			->orderByRaw("CASE WHEN currency_id = ? THEN 0 ELSE 1 END", [$currencyId])
			->orderBy('id')
			->first();

		if ($byCurrencyId) {
			return $byCurrencyId;
		}

		$currencyCode = str_contains($currencyId, ':')
			? explode(':', $currencyId, 2)[1]
			: $currencyId;

		return (clone $base)
			->where('currency_code', $currencyCode)
			->orderBy('id')
			->first();
	}

	private function createGrantAndCredit(
		Player $player,
		Wallet $wallet,
		int $decimals,
		string $amountBase,
		?int $bonusRuleId,
		string $sourceType,
		?string $sourceRef,
		mixed $expiresAt,
		string $wageringRequiredBase,
		string $realWagerRequiredBase = '0',
		string $maxConvertToRealBase = '0',
		bool $withdrawLock = false,
		array $meta = []
	): ?BonusGrant
	{
		return DB::transaction(function () use (
			$player,
			$wallet,
			$decimals,
			$amountBase,
			$bonusRuleId,
			$sourceType,
			$sourceRef,
			$expiresAt,
			$wageringRequiredBase,
			$realWagerRequiredBase,
			$maxConvertToRealBase,
			$withdrawLock,
			$meta
		) {
			$grant = BonusGrant::query()->create([
				'bonus_rule_id' => $bonusRuleId,
				'int_casino_id' => $player->int_casino_id,
				'player_id' => $player->id,
				'wallet_id_bonus' => $wallet->id,
				'currency_id' => $wallet->currency_id,
				'currency_code' => $wallet->currency_code,
				'amount_granted_base' => $amountBase,
				'amount_remaining_base' => $amountBase,
				'status' => 'active',
				'source_type' => $sourceType,
				'source_ref' => $sourceRef,
				'expires_at' => $expiresAt,
				'wagering_required_base' => $wageringRequiredBase,
				'wagering_progress_base' => '0',
				'real_wager_required_base' => $realWagerRequiredBase,
				'real_wager_progress_base' => '0',
				'max_convert_to_real_base' => $maxConvertToRealBase,
				'converted_to_real_base' => '0',
				'withdraw_lock' => $withdrawLock ? 1 : 0,
				'meta' => $meta,
			]);

			$idempotencyKey = "bonus:grant:{$grant->id}";

			$this->ledger->creditAvailable(
				wallet: $wallet,
				type: 'bonus_grant',
				amountBase: $amountBase,
				decimals: $decimals,
				idempotencyKey: $idempotencyKey,
				referenceType: 'bonus_grant',
				referenceId: (string)$grant->id,
				meta: [
					'bonus_rule_id' => $bonusRuleId,
					'bonus_grant_id' => $grant->id,
					'player_id' => $player->id,
					'source_type' => $sourceType,
					'source_ref' => $sourceRef,
				]
			);

			BonusGrantEvent::query()->create([
				'bonus_grant_id' => $grant->id,
				'event_type' => 'issued',
				'amount_base' => $amountBase,
				'idempotency_key' => $idempotencyKey,
				'reference_type' => $bonusRuleId ? 'bonus_rule' : 'manual',
				'reference_id' => $bonusRuleId ? (string)$bonusRuleId : (string)$sourceRef,
				'meta' => [
					'player_id' => $player->id,
					'wallet_id_bonus' => $wallet->id,
				],
			]);

			try {
				$player->notify(new BonusActivatedNotification(
					$grant,
					CurrencyDecimals::uiForCurrency((string)$grant->currency_id)
				));
			} catch (\Throwable $e) {
				Log::error('bonus.notification.failed', [
					'player_id' => $player->id,
					'bonus_grant_id' => $grant->id,
					'error' => $e->getMessage(),
				]);
			}

			return $grant;
		});
	}

	private function resolveRewardAmountBase(BonusRule $rule, int $decimals, ?string $depositAmountBase = null): string
	{
		$depositAmountBase = $depositAmountBase !== null ? trim($depositAmountBase) : null;
		$hasDepositContext = $depositAmountBase !== null && $depositAmountBase !== '' && bccomp($depositAmountBase, '0', 0) === 1;

		if ((string)$rule->trigger_type === 'deposit' && $hasDepositContext && $rule->deposit_bonus_multiplier !== null) {
			// Explicit multiplier for deposit campaigns: bonus = deposit * multiplier.
			return bcdiv(
				bcmul($depositAmountBase, (string)$rule->deposit_bonus_multiplier, 8),
				'1',
				0
			);
		}

		if ($rule->reward_type === 'fixed_amount') {
			$uiAmount = (string)$rule->reward_value;
			if ($rule->max_reward_amount !== null) {
				$uiAmount = bccomp((string)$rule->max_reward_amount, $uiAmount, 8) < 0
					? (string)$rule->max_reward_amount
					: $uiAmount;
			}

			return Money::uiToBase($uiAmount, $decimals);
		}

		if ($rule->reward_type === 'percentage' && $hasDepositContext) {
			$amountBase = bcdiv(
				bcmul($depositAmountBase, (string)$rule->reward_value, 8),
				'100',
				0
			);

			if ($rule->max_reward_amount !== null) {
				$maxBase = Money::uiToBase((string)$rule->max_reward_amount, $decimals);
				$amountBase = bccomp($amountBase, $maxBase, 0) === 1 ? $maxBase : $amountBase;
			}

			return $amountBase;
		}

		// Percentage requires a base amount context (deposit etc.), not available for register.
		return '0';
	}
}
