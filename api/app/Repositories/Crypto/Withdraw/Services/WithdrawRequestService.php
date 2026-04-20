<?php

	namespace App\Repositories\Crypto\Withdraw\Services;

	use App\Repositories\Crypto\Contracts\TransactionWriterInterface;
	use App\Repositories\Crypto\Services\WalletLedgerService;
	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Repositories\Crypto\Support\Money;
	use App\Repositories\Crypto\Withdraw\Contracts\WithdrawRequestServiceInterface;
	use App\Models\Wallet;
	use Illuminate\Contracts\Pagination\LengthAwarePaginator;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Str;

	class WithdrawRequestService implements WithdrawRequestServiceInterface
	{
		private const PLAYER_HOLDER_TYPE = 'App\\Models\\Player';

		public function __construct(
			private WalletLedgerService        $ledger,
			private TransactionWriterInterface $txWriter
		)
		{
		}

		public function createRequest(int $playerId, int $walletId, string $toAddress, string $amountUi, array $meta = []): string
		{
			$wallet = Wallet::query()->findOrFail($walletId);

			if ($wallet->holder_type !== self::PLAYER_HOLDER_TYPE || (int)$wallet->holder_id !== $playerId) {
				throw new \RuntimeException('Invalid wallet owner');
			}

			if ($this->hasActiveWageringLock($playerId, (string)$wallet->currency_id)) {
				throw new \RuntimeException('Withdraw locked until wagering requirements are completed');
			}

			$decimals = CurrencyDecimals::internalForWallet($wallet);
			$uiDecimals = CurrencyDecimals::uiForWallet($wallet);
			if ($uiDecimals === 0 && str_contains((string)$amountUi, '.')) {
				throw new \RuntimeException('Amount must be a whole number');
			}
			$amountBase = $this->uiToBase($amountUi, $decimals);

			if (bccomp($amountBase, '0', 0) !== 1) {
				throw new \RuntimeException('Invalid amount');
			}

			$uuid = (string)Str::uuid();

			DB::transaction(function () use ($uuid, $wallet, $playerId, $toAddress, $amountUi, $amountBase, $decimals, $meta) {
				$this->ledger->ensureBalanceRow($wallet);

				// reserve funds
				$this->ledger->reserveAvailable(
					wallet: $wallet,
					amountBase: $amountBase,
					decimals: $decimals,
					idempotencyKey: "withdraw_request:{$uuid}",
					meta: ['to_address' => $toAddress]
				);

				// 2) Create money transaction row NOW (audit/reporting)
				// status = pending, txid = null
				$tx = $this->txWriter->writeWithdrawTransaction(
					wallet: $wallet,
					status: 'pending',
					amountBase: $amountBase,
					decimals: $decimals,
					txid: null,
					toAddress: $toAddress,
					meta: array_merge($meta, [
						'withdraw_request_uuid' => $uuid,
						'kind' => 'manual',
						'stage' => 'requested',
					])
				);

				DB::table('withdraw_requests')->insert([
					'uuid' => $uuid,
					'transaction_uuid' => $tx->uuid,
					'wallet_id' => $wallet->id,
					'player_id' => $playerId,
					'currency' => $wallet->currency,
					'amount_base' => $amountBase,
					'decimals' => $decimals,
					'amount_ui' => $amountUi,
					'to_address' => $toAddress,
					'status' => 'pending',
					'meta' => json_encode($meta),
					'created_at' => now(),
					'updated_at' => now(),
				]);
			});

			event(new \App\Events\WithdrawRequested($uuid));

			return $uuid;
		}

		private function hasActiveWageringLock(int $playerId, string $currencyId): bool
		{
			return DB::table('bonus_grants as bg')
				->join('wallets as bw', 'bw.id', '=', 'bg.wallet_id_bonus')
				->where('bg.player_id', $playerId)
				->where('bw.currency_id', $currencyId)
				->whereIn('bg.status', ['active', 'granted', 'consumed'])
				->where(function ($q) {
					$q->where('bg.withdraw_lock', 1)
						->orWhereRaw('bg.wagering_progress_base < bg.wagering_required_base')
						->orWhereRaw('bg.real_wager_progress_base < bg.real_wager_required_base');
				})
				->exists();
		}

		public function paginate(array $filters): LengthAwarePaginator
		{
			$q = DB::table('withdraw_requests as wr')
				->join('players as p', 'p.id', '=', 'wr.player_id')
				->select([
					'wr.*',
					'p.username',
				])
				->orderByDesc('wr.id');

			if (!empty($filters['status'])) $q->where('wr.status', $filters['status']);
			if (!empty($filters['currency'])) $q->where('wr.currency', $filters['currency']);
			if (!empty($filters['from'])) $q->where('wr.created_at', '>=', $filters['from']);
			if (!empty($filters['to'])) $q->where('wr.created_at', '<', $filters['to']);
			if (!empty($filters['int_casino_id'])) $q->where('p.int_casino_id', '=', $filters['int_casino_id']);

			$perPage = max(10, min((int)($filters['per_page'] ?? 25), 100));
			$paginator = $q->paginate($perPage);
			$paginator->getCollection()->transform(function ($row) {
				$currency = (string)($row->currency ?? '');
				$internalDecimals = CurrencyDecimals::internalForCurrency($currency);
				$uiDecimals = CurrencyDecimals::uiForCurrency($currency);
				$amountBase = (string)($row->amount_base ?? '0');

				$row->amount_ui_raw = $row->amount_ui;
				$row->amount_ui = Money::baseToUi($amountBase, $internalDecimals, $uiDecimals);
				$row->decimals = $internalDecimals;
				$row->ui_decimals = $uiDecimals;

				if (is_string($row->meta)) {
					$decoded = json_decode($row->meta, true);
					if (json_last_error() === JSON_ERROR_NONE) {
						$row->meta = $decoded;
					}
				}

				return $row;
			});

			return $paginator;
		}

		public function listForPlayerLast24h(int $playerId): \Illuminate\Support\Collection
		{
			$from = now()->subHours(24);

			return DB::table('withdraw_requests')
				->where('player_id', $playerId)
				->where('created_at', '>=', $from)
				->where('status', '=', 'pending')
				->orderByDesc('id')
				->get([
					'uuid',
					'currency',
					'amount_base',
					'amount_ui',
					'decimals',
					'to_address',
					'status',
					'admin_note',
					'reject_reason',
					'txid',
					'completed_at',
					'created_at',
					'updated_at',
				]);
		}

		public function approve(string $uuid, int $adminId, ?string $note = null): void
		{
			$updated = DB::table('withdraw_requests')
				->where('uuid', $uuid)
				->where('status', 'pending')
				->update([
					'status' => 'approved',
					'admin_note' => $note,
					'updated_at' => now(),
				]);

			if ($updated) {
				// Optional: update transaction meta stage
				$wr = DB::table('withdraw_requests')->where('uuid', $uuid)->first(['transaction_uuid']);
				if ($wr?->transaction_uuid) {
					DB::table('transaction')->where('uuid', $wr->transaction_uuid)->update([
						'meta' => DB::raw("JSON_SET(COALESCE(meta,'{}'), '$.stage', 'approved', '$.admin_id', {$adminId})"),
						'updated_at' => now(),
					]);
				}

				event(new \App\Events\WithdrawApproved($uuid));
			}
		}

		public function reject(string $uuid, int $adminId, string $reason): void
		{
			DB::transaction(function () use ($uuid, $reason) {
				$wr = DB::table('withdraw_requests')->where('uuid', $uuid)->lockForUpdate()->first();
				if (!$wr || !in_array($wr->status, ['pending', 'approved'], true)) return;

				$wallet = Wallet::query()->findOrFail((int)$wr->wallet_id);

				$this->ledger->releaseReserved(
					wallet: $wallet,
					amountBase: (string)$wr->amount_base,
					decimals: (int)$wr->decimals,
					idempotencyKey: "withdraw_reject:{$uuid}",
					meta: ['reason' => $reason]
				);

				// mark transaction failed (if exists)
				if (!empty($wr->transaction_uuid)) {
					DB::table('transaction')->where('uuid', $wr->transaction_uuid)->update([
						'status' => 'failed',
						'meta' => DB::raw("JSON_SET(COALESCE(meta,'{}'), '$.stage', 'rejected', '$.reason', " . DB::getPdo()->quote($reason) . ", '$.admin_id', {$adminId})"),
						'updated_at' => now(),
					]);
				}

				DB::table('withdraw_requests')->where('uuid', $uuid)->update([
					'status' => 'rejected',
					'reject_reason' => $reason,
					'updated_at' => now(),
				]);
			});

			event(new \App\Events\WithdrawRejected($uuid));
		}

		public function complete(string $uuid, int $adminId, ?string $txid = null, ?string $note = null): void
		{
			DB::transaction(function () use ($uuid, $adminId, $txid, $note) {
				$wr = DB::table('withdraw_requests')->where('uuid', $uuid)->lockForUpdate()->first();
				if (!$wr || !in_array($wr->status, ['pending', 'approved'], true)) {
					throw new \RuntimeException('Invalid request state');
				}

				$wallet = Wallet::query()->findOrFail((int)$wr->wallet_id);

				// consume reserve (reserved decreases)
				$this->ledger->consumeReserve(
					wallet: $wallet,
					amountBase: (string)$wr->amount_base,
					decimals: (int)$wr->decimals,
					idempotencyKey: "withdraw:complete:{$uuid}",
					referenceId: $uuid,
					meta: ['txid' => $txid, 'admin_id' => $adminId]
				);

				// Update transaction to confirmed (DO NOT insert a new one)
				if (!empty($wr->transaction_uuid)) {
					DB::table('transaction')->where('uuid', $wr->transaction_uuid)->update([
						'status' => 'confirmed',
						'txid' => $txid,
						'to_address' => $wr->to_address,
						'meta' => DB::raw("JSON_SET(COALESCE(meta,'{}'), '$.stage', 'completed', '$.admin_id', {$adminId})"),
						'updated_at' => now(),
					]);
				} else {
					// fallback: if old rows exist without transaction_uuid, create it now
					$tx = $this->txWriter->writeWithdrawTransaction(
						wallet: $wallet,
						status: 'confirmed',
						amountBase: (string)$wr->amount_base,
						decimals: (int)$wr->decimals,
						txid: $txid,
						toAddress: $wr->to_address,
						meta: [
							'withdraw_request_uuid' => $uuid,
							'kind' => 'manual',
							'stage' => 'completed',
							'admin_id' => $adminId,
						]
					);

					DB::table('withdraw_requests')->where('uuid', $uuid)->update([
						'transaction_uuid' => $tx->uuid,
					]);
				}

				DB::table('withdraw_requests')->where('uuid', $uuid)->update([
					'status' => 'completed',
					'txid' => $txid,
					'admin_note' => $note,
					'completed_at' => now()->timestamp,
					'updated_at' => now(),
				]);
			});

			event(new \App\Events\WithdrawCompleted($uuid));
		}

		private function uiToBase(string $ui, int $decimals): string
		{
			return Money::uiToBase($ui, $decimals);
		}
	}
