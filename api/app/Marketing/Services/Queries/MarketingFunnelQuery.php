<?php

	namespace App\Marketing\Services\Queries;

	use Carbon\Carbon;
	use Illuminate\Support\Facades\DB;

	class MarketingFunnelQuery
	{
		private const PLAYER_HOLDER_TYPE = 'App\\Models\\Player';

		/**
		 * Funnel definition:
		 * Cohort = users registered in [from,to).
		 * Then count how many reached each step at ANY TIME, or within window (configurable).
		 *
		 * Here we do "within window" for deposit + first bet, because marketing wants activation in period.
		 * If you prefer lifetime after registration, I can adjust.
		 */
		public function build(Carbon $from, Carbon $to, string $currencyDb, string $casinoId): array
		{
			$symbol = $this->symbolFromDbCurrency($currencyDb);

			// 1) Registered cohort (users created in range)
			$registeredQ = DB::table('players as p')
				->where('p.created_at', '>=', $from)
				->where('p.created_at', '<', $to)
				->where('p.int_casino_id', '=', $casinoId)
				->select('p.id');

			$registeredCount = (int)DB::query()->fromSub($registeredQ, 'r')->count();

			// 2) Deposited (at least 1 confirmed deposit in range) for cohort users
			$depositedQ = DB::table('transaction as t')
				->join('wallets as w', 'w.id', '=', 't.wallet_id')
				->where('w.holder_type', self::PLAYER_HOLDER_TYPE)
				->where('t.type', 'deposit')
				->where('t.status', 'confirmed')
				->where('t.currency', $currencyDb)
				->where('t.created_at', '>=', $from)
				->where('t.created_at', '<', $to)
				->where('t.int_casino_id', '=', $casinoId)
				->select('w.holder_id')
				->distinct();

			// deposited among registered cohort
			$depositedCount = (int)DB::query()
				->fromSub($registeredQ, 'r')
				->joinSub($depositedQ, 'd', 'd.holder_id', '=', 'r.id')
				->distinct('r.id')
				->count('r.id');

			// 3) Placed first bet (at least 1 bet in range) for cohort users
			$bettorsQ = DB::table('bets as b')
				->where('b.transaction_type', 'bet')
				->where('b.currency', $symbol)
				->where('b.when_placed', '>=', $from)
				->where('b.when_placed', '<', $to)
				->where('b.int_casino_id', '=', $casinoId)
				->select('b.user_id')
				->distinct();

			$firstBetCount = (int)DB::query()
				->fromSub($registeredQ, 'r')
				->joinSub($bettorsQ, 'bt', 'bt.user_id', '=', 'r.id')
				->distinct('r.id')
				->count('r.id');

			// 4) >= 10 bets in range
			$bets10Q = DB::table('bets as b')
				->where('b.transaction_type', 'bet')
				->where('b.currency', $symbol)
				->where('b.when_placed', '>=', $from)
				->where('b.when_placed', '<', $to)
				->where('b.int_casino_id', '=', $casinoId)
				->groupBy('b.user_id')
				->havingRaw('COUNT(*) >= 10')
				->select('b.user_id');

			$bets10Count = (int)DB::query()
				->fromSub($registeredQ, 'r')
				->joinSub($bets10Q, 'b10', 'b10.user_id', '=', 'r.id')
				->distinct('r.id')
				->count('r.id');

			// 5) >= 100 bets in range
			$bets100Q = DB::table('bets as b')
				->where('b.transaction_type', 'bet')
				->where('b.currency', $symbol)
				->where('b.when_placed', '>=', $from)
				->where('b.when_placed', '<', $to)
				->where('b.int_casino_id', '=', $casinoId)
				->groupBy('b.user_id')
				->havingRaw('COUNT(*) >= 100')
				->select('b.user_id');

			$bets100Count = (int)DB::query()
				->fromSub($registeredQ, 'r')
				->joinSub($bets100Q, 'b100', 'b100.user_id', '=', 'r.id')
				->distinct('r.id')
				->count('r.id');

			// Optional: first withdraw in range
			$withdrawQ = DB::table('transaction as t')
				->join('wallets as w', 'w.id', '=', 't.wallet_id')
				->where('w.holder_type', self::PLAYER_HOLDER_TYPE)
				->where('t.type', 'withdraw')
				->whereIn('t.status', ['pending', 'confirmed']) // în funcție de cum vrei să o numeri
				->where('t.currency', $currencyDb)
				->where('t.created_at', '>=', $from)
				->where('t.created_at', '<', $to)
				->where('t.int_casino_id', '=', $casinoId)
				->select('w.holder_id')
				->distinct();

			$withdrawCount = (int)DB::query()
				->fromSub($registeredQ, 'r')
				->joinSub($withdrawQ, 'wd', 'wd.holder_id', '=', 'r.id')
				->distinct('r.id')
				->count('r.id');

			$steps = [
				['key' => 'registered', 'label' => 'Registered', 'count' => $registeredCount],
				['key' => 'deposited', 'label' => 'Deposited', 'count' => $depositedCount],
				['key' => 'first_bet', 'label' => 'Placed first bet', 'count' => $firstBetCount],
				['key' => 'bets_10', 'label' => '≥10 bets', 'count' => $bets10Count],
				['key' => 'bets_100', 'label' => '≥100 bets', 'count' => $bets100Count],
				['key' => 'withdraw', 'label' => 'Withdraw requested', 'count' => $withdrawCount],
			];

			// attach rates and drop-offs
			$steps = $this->decorate($steps);

			return [
				'currency_db' => $currencyDb,
				'currency_symbol' => $symbol,
				'steps' => $steps,
			];
		}

		private function decorate(array $steps): array
		{
			$first = $steps[0]['count'] ?? 0;
			$prev = null;

			foreach ($steps as $i => $s) {
				$count = (int)($s['count'] ?? 0);

				$steps[$i]['rate_from_registered'] = $first > 0 ? round($count / $first, 4) : 0;
				if ($prev === null) {
					$steps[$i]['rate_from_prev'] = 1;
					$steps[$i]['drop_from_prev'] = 0;
				} else {
					$steps[$i]['rate_from_prev'] = $prev > 0 ? round($count / $prev, 4) : 0;
					$steps[$i]['drop_from_prev'] = $prev > 0 ? round(1 - ($count / $prev), 4) : 0;
				}
				$prev = $count;
			}

			return $steps;
		}

		private function symbolFromDbCurrency(string $dbCurrency): string
		{
			return str_contains($dbCurrency, ':') ? explode(':', $dbCurrency, 2)[1] : $dbCurrency;
		}
	}
