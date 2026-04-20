<?php

	namespace App\Repositories\Crypto\Services;

	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Repositories\Crypto\Support\Money;
	use App\Models\Wallet;
	use App\Models\WalletBalance;
	use App\Models\WalletLedgerEntry;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Log;

	class WalletLedgerService
	{
		/**
		 * Ensure balance row exists (call when wallet created).
		 */
		public function ensureBalanceRow(Wallet $wallet): void
		{
			WalletBalance::firstOrCreate(
				['wallet_id' => $wallet->id],
				[
					'currency' => $wallet->currency,
					'available_base' => 0,
					'reserved_base' => 0,
				]
			);
		}

		// ------------------------------------------------------------
		//  Core primitive: apply delta on available/reserved atomically
		// ------------------------------------------------------------

		/**
		 * Applies deltas to wallet_balances with idempotency.
		 * Returns true if applied (new), false if already applied.
		 *
		 * NOTE: Callers should pass integer strings for deltas.
		 */
		private function applyDelta(
			Wallet  $wallet,
			string  $type,
			string  $direction,
			string  $deltaAvailableBase,
			string  $deltaReservedBase,
			int     $decimals,
			string  $idempotencyKey,
			?string $referenceType = null,
			?string $referenceId = null,
			array   $meta = []
		): bool
		{
			$deltaAvailableBase = $this->normInt($deltaAvailableBase);
			$deltaReservedBase = $this->normInt($deltaReservedBase);

			return DB::transaction(function () use (
				$wallet,
				$type,
				$direction,
				$deltaAvailableBase,
				$deltaReservedBase,
				$decimals,
				$idempotencyKey,
				$referenceType,
				$referenceId,
				$meta
			) {
				$this->ensureBalanceRow($wallet);

				/** @var WalletBalance $bal */
				$bal = WalletBalance::where('wallet_id', $wallet->id)->lockForUpdate()->firstOrFail();

				// fast idempotency check (unique is best)
				$exists = WalletLedgerEntry::where('idempotency_key', $idempotencyKey)->exists();
				if ($exists) {
					Log::channel('crypto')->info('wallet_ledger.idempotent_skip', [
						'wallet_id' => $wallet->id,
						'idempotency_key' => $idempotencyKey,
						'type' => $type,
					]);
					return false;
				}

				$beforeAvail = $this->normInt((string)($bal->available_base ?? '0'));
				$beforeRes = $this->normInt((string)($bal->reserved_base ?? '0'));

				$afterAvail = bcadd($beforeAvail, $deltaAvailableBase, 0);
				$afterRes = bcadd($beforeRes, $deltaReservedBase, 0);

				if (bccomp($afterAvail, '0', 0) === -1) {
					throw new \RuntimeException('INSUFFICIENT_FUNDS');
				}
				if (bccomp($afterRes, '0', 0) === -1) {
					throw new \RuntimeException('INSUFFICIENT_RESERVED');
				}

				// insert ledger entry first (idempotency)
				try {
					WalletLedgerEntry::create([
						'wallet_id' => $wallet->id,
						'currency' => $wallet->currency,

						'type' => $type,
						'direction' => $direction,

						// keep old fields compatible:
						'amount_base' => '0',
						'decimals' => $decimals,

						'reference_type' => $referenceType,
						'reference_id' => $referenceId,
						'idempotency_key' => $idempotencyKey,
						'meta' => array_merge($meta, [
							'delta_available_base' => $deltaAvailableBase,
							'delta_reserved_base' => $deltaReservedBase,
							'available_before' => $beforeAvail,
							'reserved_before' => $beforeRes,
							'available_after' => $afterAvail,
							'reserved_after' => $afterRes,
						]),
					]);
				} catch (\Illuminate\Database\QueryException $e) {
					// 23000 = duplicate key (MySQL)
					if ((int)$e->getCode() === 23000) {
						Log::channel('crypto')->info('wallet_ledger.idempotent_skip', [
							'wallet_id' => $wallet->id,
							'idempotency_key' => $idempotencyKey,
							'type' => $type,
						]);
						return false;
					}
					throw $e;
				}

				// apply new balances
				$bal->available_base = $afterAvail;
				$bal->reserved_base = $afterRes;
				$bal->save();

				Log::channel('crypto')->info('wallet_ledger.applied', [
					'wallet_id' => $wallet->id,
					'type' => $type,
					'idempotency_key' => $idempotencyKey,
					'available_before' => $beforeAvail,
					'available_after' => $afterAvail,
					'reserved_before' => $beforeRes,
					'reserved_after' => $afterRes,
				]);

				return true;
			});
		}

		// ---------------------------
		//  Existing API (deposit/bet)
		// ---------------------------

		public function creditAvailable(
			Wallet  $wallet,
			string  $type,
			string  $amountBase,
			int     $decimals,
			string  $idempotencyKey,
			?string $referenceType = null,
			?string $referenceId = null,
			array   $meta = []
		): bool
		{
			$amountBase = $this->normInt($amountBase);

			// deltaAvailable +amount, reserved 0
			return $this->applyDelta(
				wallet: $wallet,
				type: $type,
				direction: 'credit',
				deltaAvailableBase: $amountBase,
				deltaReservedBase: '0',
				decimals: $decimals,
				idempotencyKey: $idempotencyKey,
				referenceType: $referenceType,
				referenceId: $referenceId,
				meta: $meta
			);
		}

		public function debitAvailable(
			Wallet  $wallet,
			string  $type,
			string  $amountBase,
			int     $decimals,
			string  $idempotencyKey,
			?string $referenceType = null,
			?string $referenceId = null,
			array   $meta = []
		): bool
		{
			$amountBase = $this->normInt($amountBase);

			// deltaAvailable -amount, reserved 0
			return $this->applyDelta(
				wallet: $wallet,
				type: $type,
				direction: 'debit',
				deltaAvailableBase: bcmul($amountBase, '-1', 0),
				deltaReservedBase: '0',
				decimals: $decimals,
				idempotencyKey: $idempotencyKey,
				referenceType: $referenceType,
				referenceId: $referenceId,
				meta: $meta
			);
		}

		// -------------------------------------------------
		//  Withdraw Requests API (NEW methods we need)
		// -------------------------------------------------

		/**
		 * Move funds: available -> reserved (withdraw pending).
		 */
		public function reserveAvailable(
			Wallet  $wallet,
			string  $amountBase,
			int     $decimals,
			string  $idempotencyKey,
			array   $meta = [],
			?string $referenceType = 'internal',
			?string $referenceId = null
		): bool
		{
			$amountBase = $this->normInt($amountBase);

			return $this->applyDelta(
				wallet: $wallet,
				type: 'reserve',
				direction: 'debit',
				deltaAvailableBase: bcmul($amountBase, '-1', 0),
				deltaReservedBase: $amountBase,
				decimals: $decimals,
				idempotencyKey: $idempotencyKey,
				referenceType: $referenceType,
				referenceId: $referenceId,
				meta: $meta
			);
		}

		/**
		 * Move funds back: reserved -> available (withdraw rejected/failed).
		 */
		public function releaseReserved(
			Wallet  $wallet,
			string  $amountBase,
			int     $decimals,
			string  $idempotencyKey,
			array   $meta = [],
			?string $referenceType = 'internal',
			?string $referenceId = null
		): bool
		{
			$amountBase = $this->normInt($amountBase);

			return $this->applyDelta(
				wallet: $wallet,
				type: 'release_reserve',
				direction: 'credit',
				deltaAvailableBase: $amountBase,
				deltaReservedBase: bcmul($amountBase, '-1', 0),
				decimals: $decimals,
				idempotencyKey: $idempotencyKey,
				referenceType: $referenceType,
				referenceId: $referenceId,
				meta: $meta
			);
		}

		/**
		 * Finalize withdraw: reserved decreases (liability goes down), available unchanged.
		 */
		public function finalizeReservedOutflow(
			Wallet  $wallet,
			string  $amountBase,
			int     $decimals,
			string  $idempotencyKey,
			array   $meta = [],
			?string $referenceType = 'internal',
			?string $referenceId = null
		): bool
		{
			$amountBase = $this->normInt($amountBase);

			return $this->applyDelta(
				wallet: $wallet,
				type: 'consume_reserve',
				direction: 'debit',
				deltaAvailableBase: '0',
				deltaReservedBase: bcmul($amountBase, '-1', 0),
				decimals: $decimals,
				idempotencyKey: $idempotencyKey,
				referenceType: $referenceType,
				referenceId: $referenceId,
				meta: $meta
			);
		}

		// ---------------------------
		// Backward-compatible aliases
		// ---------------------------

		public function reserve(
			Wallet  $wallet,
			string  $amountBase,
			int     $decimals,
			string  $idempotencyKey,
			?string $referenceId = null,
			array   $meta = []
		): bool
		{
			return $this->reserveAvailable($wallet, $amountBase, $decimals, $idempotencyKey, $meta, 'internal', $referenceId);
		}

		public function releaseReserve(
			Wallet  $wallet,
			string  $amountBase,
			int     $decimals,
			string  $idempotencyKey,
			?string $referenceId = null,
			array   $meta = []
		): bool
		{
			return $this->releaseReserved($wallet, $amountBase, $decimals, $idempotencyKey, $meta, 'internal', $referenceId);
		}

		public function consumeReserve(
			Wallet  $wallet,
			string  $amountBase,
			int     $decimals,
			string  $idempotencyKey,
			?string $referenceId = null,
			array   $meta = []
		): bool
		{
			return $this->finalizeReservedOutflow($wallet, $amountBase, $decimals, $idempotencyKey, $meta, 'internal', $referenceId);
		}

		// ---------------------------
		// UI helpers
		// ---------------------------

		public function getAvailableUi(Wallet $wallet, int $scale = 8): string
		{
			$this->ensureBalanceRow($wallet);
			$row = WalletBalance::query()->where('wallet_id', $wallet->id)->first();

			$availableBase = (string)($row->available_base ?? '0');
			$decimals = $this->resolveDecimals($wallet);

			return Money::baseToUi($availableBase, $decimals, $scale);
		}

		public function getReservedUi(Wallet $wallet, int $scale = 8): string
		{
			$this->ensureBalanceRow($wallet);
			$row = WalletBalance::query()->where('wallet_id', $wallet->id)->first();

			$reservedBase = (string)($row->reserved_base ?? '0');
			$decimals = $this->resolveDecimals($wallet);

			return Money::baseToUi($reservedBase, $decimals, $scale);
		}

		public function getTotalUi(Wallet $wallet, int $scale = 8): string
		{
			$this->ensureBalanceRow($wallet);
			$row = WalletBalance::query()->where('wallet_id', $wallet->id)->first();

			$available = (string)($row->available_base ?? '0');
			$reserved = (string)($row->reserved_base ?? '0');
			$totalBase = bcadd($this->normInt($available), $this->normInt($reserved), 0);

			$decimals = $this->resolveDecimals($wallet);

			return Money::baseToUi($totalBase, $decimals, $scale);
		}

		// ---------------------------
		// Utils
		// ---------------------------

		private function resolveDecimals(Wallet $wallet): int
		{
			return CurrencyDecimals::internalForWallet($wallet);
		}

		private function normInt(string $v): string
		{
			$v = trim((string)$v);
			if ($v === '' || $v === null) return '0';

			$neg = false;
			if (str_starts_with($v, '-')) {
				$neg = true;
				$v = substr($v, 1);
			}

			// remove non-digits (safety)
			$v = preg_replace('/\D+/', '', $v) ?? '0';

			$v = ltrim($v, '0');
			if ($v === '') $v = '0';

			return $neg ? ('-' . $v) : $v;
		}
	}
