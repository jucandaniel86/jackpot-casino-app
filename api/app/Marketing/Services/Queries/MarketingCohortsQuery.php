<?php

	namespace App\Marketing\Services\Queries;

	use Carbon\Carbon;
	use Illuminate\Support\Facades\DB;

	class MarketingCohortsQuery
	{
		/**
		 * Weekly cohorts based on players.created_at.
		 * Retention definition: user has at least 1 BET on day N after signup.
		 */
		public function retention(Carbon $from, Carbon $to, string $currencyDb, string $casinoId): array
		{
			$symbol = $this->symbolFromDbCurrency($currencyDb);

			// cohort label: YEARWEEK(created_at, 3) or DATE_FORMAT
			$cohorts = DB::table('players as p')
				->where('p.created_at', '>=', $from)
				->where('p.created_at', '<', $to)
				->where('p.int_casino_id', '=', $casinoId)
				->selectRaw("
                YEARWEEK(p.created_at, 3) as cohort_key,
                MIN(DATE(p.created_at)) as cohort_start,
                COUNT(*) as users
            ")
				->groupBy('cohort_key')
				->orderBy('cohort_key')
				->get();

			$out = [];
			foreach ($cohorts as $c) {
				$cohortKey = (string)$c->cohort_key;
				$users = (int)$c->users;

				// D1 / D7 / D30 active = at least one bet on that day after signup
				$d1 = $this->retainedCount($cohortKey, 1, $symbol, $casinoId);
				$d7 = $this->retainedCount($cohortKey, 7, $symbol, $casinoId);
				$d30 = $this->retainedCount($cohortKey, 30, $symbol, $casinoId);

				$out[] = [
					'cohort_key' => $cohortKey,
					'cohort_start' => (string)$c->cohort_start,
					'users' => $users,
					'd1' => ['count' => $d1, 'rate' => $users > 0 ? round($d1 / $users, 4) : 0],
					'd7' => ['count' => $d7, 'rate' => $users > 0 ? round($d7 / $users, 4) : 0],
					'd30' => ['count' => $d30, 'rate' => $users > 0 ? round($d30 / $users, 4) : 0],
				];
			}

			return [
				'definition' => 'Weekly cohorts by players.created_at. Retention = at least 1 bet on day N after signup.',
				'currency_symbol' => $symbol,
				'rows' => $out,
			];
		}

		private function retainedCount(string $cohortKey, int $dayOffset, string $symbol, string $casinoId): int
		{
			// cohort users
			$cohortUsers = DB::table('players as p')
				->whereRaw("YEARWEEK(p.created_at, 3) = ?", [$cohortKey])
				->where('p.int_casino_id', '=', $casinoId)
				->select('p.id', DB::raw('DATE(p.created_at) as d0'));

			// users who bet exactly on d0 + dayOffset (any bet that day)
			$ret = DB::query()
				->fromSub($cohortUsers, 'cu')
				->join('bets as b', 'b.user_id', '=', 'cu.id')
				->where('b.transaction_type', 'bet')
				->where('b.currency', $symbol)
				->where('b.int_casino_id', $casinoId)
				->whereRaw("DATE(b.when_placed) = DATE_ADD(cu.d0, INTERVAL ? DAY)", [$dayOffset])
				->distinct('cu.id')
				->count('cu.id');

			return (int)$ret;
		}

		private function symbolFromDbCurrency(string $dbCurrency): string
		{
			return str_contains($dbCurrency, ':') ? explode(':', $dbCurrency, 2)[1] : $dbCurrency;
		}
	}
