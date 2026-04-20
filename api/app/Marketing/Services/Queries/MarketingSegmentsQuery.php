<?php

	namespace App\Marketing\Services\Queries;

	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use Carbon\Carbon;
	use Illuminate\Support\Facades\DB;

	class MarketingSegmentsQuery
	{
		private const PLAYER_HOLDER_TYPE = 'App\\Models\\Player';

		public function overview(Carbon $from, Carbon $to, string $currencyDb, string $casinoId, array $opts = []): array
		{
			$symbol = $this->symbolFromDbCurrency($currencyDb);
			$inactiveDays = (int)($opts['inactive_days'] ?? 7);
			$limit = (int)($opts['limit'] ?? 100);

			$neverPlayed = $this->neverPlayed($currencyDb, $symbol, $casinoId, $limit);
			$atRisk = $this->atRisk($currencyDb, $symbol, $inactiveDays, $casinoId, $limit);
			$highValue = $this->highValue($currencyDb, $casinoId, $limit);

			return [
				'currency_db' => $currencyDb,
				'currency_symbol' => $symbol,
				'inactive_days' => $inactiveDays,
				'segments' => [
					'never_played' => $neverPlayed,
					'at_risk' => $atRisk,
					'high_value' => $highValue,
				],
			];
		}

		/**
		 * Deposited ever (confirmed) but 0 bets ever.
		 */
		private function neverPlayed(string $currencyDb, string $symbol, $casinoId, int $limit): array
		{
			$depositUsers = DB::table('transaction as t')
				->join('wallets as w', 'w.id', '=', 't.wallet_id')
				->where('t.type', 'deposit')
				->where('t.status', 'confirmed')
				->where('t.currency', $currencyDb)
				->where('t.int_casino_id', $casinoId)
				->where('w.holder_type', self::PLAYER_HOLDER_TYPE)
				->select('w.holder_id')
				->distinct();

			$bettedUsers = DB::table('bets as b')
				->where('b.transaction_type', 'bet')
				->where('b.currency', $symbol)
				->where('b.int_casino_id', $casinoId)
				->select('b.user_id')
				->distinct();

			$q = DB::query()
				->fromSub($depositUsers, 'du')
				->join('players as p', 'p.id', '=', 'du.holder_id')
				->whereNotIn('du.holder_id', $bettedUsers)
				->select('p.id', 'p.username', 'p.created_at')
				->orderByDesc('p.id')
				->limit($limit);

			$rows = $q->get();

			return [
				'count' => (int)$q->getCountForPagination(),
				'items' => $rows->map(fn($r) => [
					'player_id' => (int)$r->id,
					'username' => (string)$r->username,
					'created_at' => (string)$r->created_at,
				])->toArray(),
			];
		}

		/**
		 * Deposited ever, but no bet in last N days.
		 */
		private function atRisk(string $currencyDb, string $symbol, int $inactiveDays, string $casinoId, int $limit): array
		{
			$cutoff = now()->subDays($inactiveDays);

			$depositUsers = DB::table('transaction as t')
				->join('wallets as w', 'w.id', '=', 't.wallet_id')
				->where('t.type', 'deposit')
				->where('t.status', 'confirmed')
				->where('t.currency', $currencyDb)
				->where('w.holder_type', self::PLAYER_HOLDER_TYPE)
				->where('t.int_casino_id', $casinoId)
				->select('w.holder_id')
				->distinct();

			$recentBettors = DB::table('bets as b')
				->where('b.transaction_type', 'bet')
				->where('b.currency', $symbol)
				->where('b.when_placed', '>=', $cutoff)
				->where('b.int_casino_id', $casinoId)
				->select('b.user_id')
				->distinct();

			$q = DB::query()
				->fromSub($depositUsers, 'du')
				->join('players as p', 'p.id', '=', 'du.holder_id')
				->whereNotIn('du.holder_id', $recentBettors)
				->select('p.id', 'p.username', 'p.created_at')
				->orderByDesc('p.id')
				->limit($limit);

			$rows = $q->get();

			return [
				'cutoff' => $cutoff->toISOString(),
				'count' => (int)$q->getCountForPagination(),
				'items' => $rows->map(fn($r) => [
					'player_id' => (int)$r->id,
					'username' => (string)$r->username,
					'created_at' => (string)$r->created_at,
				])->toArray(),
			];
		}

		/**
		 * High value: top users by deposits sum_base in range (or lifetime).
		 * Here: lifetime sum for simplicity.
		 */
		private function highValue(string $currencyDb, string $casinoId, int $limit): array
		{
			$rows = DB::table('transaction as t')
				->join('wallets as w', 'w.id', '=', 't.wallet_id')
				->join('players as p', 'p.id', '=', 'w.holder_id')
				->where('t.type', 'deposit')
				->where('t.status', 'confirmed')
				->where('t.currency', $currencyDb)
				->where('w.holder_type', self::PLAYER_HOLDER_TYPE)
				->where('t.int_casino_id', $casinoId)
				->groupBy('p.id', 'p.username')
				->selectRaw("
                p.id as player_id,
                p.username,
                COALESCE(SUM(t.amount_base),0) as deposits_sum_base
            ")
				->orderByDesc('deposits_sum_base')
				->limit($limit)
				->get();

			return [
				'count' => $rows->count(),
				'items' => $rows->map(fn($r) => [
					'player_id' => (int)$r->player_id,
					'username' => (string)$r->username,
					'deposits_sum_base' => (string)$r->deposits_sum_base,
					'decimals' => CurrencyDecimals::internalForCurrency($currencyDb),
					'ui_decimals' => CurrencyDecimals::uiForCurrency($currencyDb),
				])->toArray(),
			];
		}

		private function symbolFromDbCurrency(string $dbCurrency): string
		{
			return str_contains($dbCurrency, ':') ? explode(':', $dbCurrency, 2)[1] : $dbCurrency;
		}
	}
