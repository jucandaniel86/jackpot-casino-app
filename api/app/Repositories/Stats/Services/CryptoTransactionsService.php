<?php


	namespace App\Repositories\Stats\Services;

	use App\Repositories\Stats\Contracts\CryptoTransactionsServiceInterface;
	use Illuminate\Contracts\Pagination\LengthAwarePaginator;
	use Illuminate\Support\Facades\DB;

	class CryptoTransactionsService implements CryptoTransactionsServiceInterface
	{
		private const PLAYER_HOLDER_TYPE = 'App\\Models\\Player';

		public function paginate(array $filters): LengthAwarePaginator
		{
			$currencyInput = strtoupper($filters['currency_code'] ?? '');
			$currencyDb = $currencyInput ? $this->dbCurrency($currencyInput) : null;

			$type = $filters['type'] ?? null;      // deposit|withdraw
			$status = $filters['status'] ?? null;  // pending|confirmed|failed

			$from = $filters['from'] ?? null;
			$to = $filters['to'] ?? null;

			$casinoID = $filters['int_casino_id'] ?? null;

			$perPage = (int)($filters['per_page'] ?? 25);
			$perPage = max(10, min($perPage, 100));

			$q = DB::table('transaction as t')
				->join('wallets as w', 'w.id', '=', 't.wallet_id')
				->leftJoin('players as p', function ($join) {
					// wallet-ul aparține playerului prin morph
					$join->on('p.id', '=', 'w.holder_id')
						->where('w.holder_type', '=', self::PLAYER_HOLDER_TYPE);
				})
				->leftJoin('casinos as c', function ($join) {
					$join->on('c.id', '=', 'w.holder_id')
						->where('w.holder_type', '=', 'App\\Models\\Casino');
				})
				->select([
					't.id',
					't.uuid',
					't.wallet_id',
					't.currency',
					't.type',
					't.status',
					't.amount_base',
					't.decimals',
					't.amount',
					't.txid',
					't.from_address',
					't.to_address',
					't.meta',
					't.block_time',
					't.created_at',
					DB::raw('COALESCE(p.username, c.username) as username'),
				]);

			if ($currencyDb) {
				$q->where('t.currency', $currencyDb);
			}

			if ($type) {
				$q->where('t.type', $type);
			}

			if ($status) {
				$q->where('t.status', $status);
			}

			if ($from) {
				$q->where('t.created_at', '>=', $from);
			}

			if ($to) {
				$q->where('t.created_at', '<', $to);
			}

			if ($casinoID) {
				$q->where('t.int_casino_id', '=', $casinoID);
			}

			// default sort: newest first
			$q->orderByDesc('t.id');

			return $q->paginate($perPage);
		}

		private function dbCurrency(string $currency): string
		{
			$currency = strtoupper($currency);
			if (str_contains($currency, ':')) return $currency;
			return "SOLANA:{$currency}";
		}
	}
