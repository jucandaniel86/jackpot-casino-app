<?php

	namespace App\Repositories\Crypto\Services;

	use App\Repositories\Crypto\Contracts\TransactionQueryServiceInterface;
	use App\Models\Transaction;
	use App\Models\Wallet;
	use Carbon\Carbon;
	use Illuminate\Contracts\Pagination\LengthAwarePaginator;

	class TransactionQueryService implements TransactionQueryServiceInterface
	{
		public function paginate(array $filters): LengthAwarePaginator
		{
			$holderType = $filters['holder_type'] ?? null;
			$holderId = $filters['holder_id'] ?? null;

			if (!$holderType || !$holderId) {
				throw new \InvalidArgumentException('Missing holder_type/holder_id');
			}

			// Wallet-urile userului (ca să nu expunem tranzacții care nu-i aparțin)
			$walletIds = Wallet::query()
				->where('holder_type', $holderType)
				->where('holder_id', (int)$holderId)
				->pluck('id')
				->all();

			$q = Transaction::query()
				->whereIn('wallet_id', $walletIds)
				->whereIn('type', ['deposit', 'withdraw'])
				->orderByDesc('created_at');

			// currency
			if (!empty($filters['currency'])) {
				$currencyCode = strtoupper(trim((string)$filters['currency']));
				$q->where(function ($sub) use ($currencyCode) {
					$sub->where('currency_code', $currencyCode)
						->orWhere('currency', $currencyCode); // legacy rows with code in currency
				});
			}

			// type
			if (!empty($filters['type'])) {
				$q->where('type', $filters['type']); // deposit|withdraw
			}

			// status (optional)
			if (!empty($filters['status'])) {
				$q->where('status', $filters['status']);
			}

			// date range
			if (!empty($filters['from'])) {
				$from = $this->parseDate($filters['from'])->startOfDay();
				$q->where('created_at', '>=', $from);
			}
			if (!empty($filters['to'])) {
				$to = $this->parseDate($filters['to'])->endOfDay();
				$q->where('created_at', '<=', $to);
			}

			if (!empty($filters['int_casino_id'])) {
				$q->where('int_casino_id', '=', $filters['int_casino_id']);
			}

			$perPage = (int)($filters['per_page'] ?? 25);
			$perPage = max(1, min($perPage, 200));

			return $q->paginate($perPage);
		}

		private function parseDate(string $value): Carbon
		{
			// Acceptă YYYY-MM-DD sau ISO
			return Carbon::parse($value);
		}
	}