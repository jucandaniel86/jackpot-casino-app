<?php

	namespace App\Repositories\Stats\Services;

	use App\Repositories\Stats\Contracts\ConversionFunnelServiceInterface;
	use Carbon\Carbon;
	use Illuminate\Support\Facades\DB;

	class ConversionFunnelService implements ConversionFunnelServiceInterface
	{
		public function report(array $filters): array
		{
			$from = Carbon::parse($filters['from']);
			$to = Carbon::parse($filters['to']);

			$currency = $filters['currency_code'] ?? null; // ex PEP
			$casinoId = $filters['int_casino_id'] ?? null;

			$t10 = (int)($filters['bets_10_threshold'] ?? 10);
			$t100 = (int)($filters['bets_100_threshold'] ?? 100);

			// 1) Cohort: registered in range
			$registeredIdsQ = DB::table('players')
				->where('created_at', '>=', $from)
				->where('created_at', '<', $to)
				->select('id');

			if ($casinoId) {
				$registeredIdsQ->where('int_casino_id', '=', $casinoId);
			}

			$registered = (clone $registeredIdsQ)->count();

			// 2) Deposited: users din cohort cu cel puțin 1 deposit confirmed
			// transaction table name: `transaction` (singular) as per schema
			// wallets are morph: holder_type/holder_id
			$depositedIdsQ = DB::table('transaction as t')
				->join('wallets as w', 'w.id', '=', 't.wallet_id')
				->whereIn('w.holder_id', $registeredIdsQ)
				->where('w.holder_type', '=', 'App\\Models\\Player')
				->where('t.type', '=', 'deposit')
				->where('t.status', '=', 'confirmed')
				->where('t.created_at', '>=', $from)
				->where('t.created_at', '<', $to);

			if ($casinoId) {
				$depositedIdsQ->where('t.int_casino_id', '=', $casinoId);
			}

			if ($currency) {
				// currency în transactions = wallet.currency
				$depositedIdsQ->where('t.currency', strtoupper($currency));
			}

			$deposited = (clone $depositedIdsQ)
				->distinct()
				->count('w.holder_id');

			// 3) Placed first bet: users din cohort cu >=1 bet row (transaction_type='bet')
			$betUsersQ = DB::table('bets as b')
				->whereIn('b.user_id', $registeredIdsQ)
				->where('b.transaction_type', '=', 'bet')
				->where('b.when_placed', '>=', $from)
				->where('b.when_placed', '<', $to);

			if ($casinoId) {
				$betUsersQ->where('b.int_casino_id', '=', $casinoId);
			}

			if ($currency) {
				$betUsersQ->where('b.currency', strtoupper($currency));
			}

			$placedFirstBet = (clone $betUsersQ)->distinct()->count('b.user_id');

			// 4) >=10 bets (în interval) pentru users din cohort
			$betsCountPerUserQ = DB::table('bets as b')
				->whereIn('b.user_id', $registeredIdsQ)
				->where('b.transaction_type', '=', 'bet')
				->where('b.when_placed', '>=', $from)
				->where('b.when_placed', '<', $to);

			if ($casinoId) {
				$betsCountPerUserQ->where('b.int_casino_id', '=', $casinoId);
			}

			if ($currency) {
				$betsCountPerUserQ->where('b.currency', strtoupper($currency));
			}

			$bets10 = DB::query()
				->fromSub(
					$betsCountPerUserQ->groupBy('b.user_id')->select('b.user_id', DB::raw('COUNT(*) as c')),
					'x'
				)
				->where('x.c', '>=', $t10)
				->count();

			// 5) >=100 bets
			$bets100 = DB::query()
				->fromSub(
					$betsCountPerUserQ->groupBy('b.user_id')->select('b.user_id', DB::raw('COUNT(*) as c')),
					'x'
				)
				->where('x.c', '>=', $t100)
				->count();

			// Conversion rates (sequential + overall)
			$steps = [
				['key' => 'registered', 'label' => 'Registered', 'count' => $registered],
				['key' => 'deposited', 'label' => 'Deposited', 'count' => $deposited],
				['key' => 'first_bet', 'label' => 'Placed first bet', 'count' => $placedFirstBet],
				['key' => 'bets_10', 'label' => "≥{$t10} bets", 'count' => $bets10],
				['key' => 'bets_100', 'label' => "≥{$t100} bets", 'count' => $bets100],
			];

			$steps = $this->addRates($steps);

			return [
				'range' => [
					'from' => $from->toISOString(),
					'to' => $to->toISOString(),
				],
				'filters' => [
					'currency_code' => $currency ? strtoupper($currency) : null,
					'bets_10_threshold' => $t10,
					'bets_100_threshold' => $t100,
				],
				'steps' => $steps,
			];
		}

		private function addRates(array $steps): array
		{
			$base = max(0, (int)($steps[0]['count'] ?? 0));
			$prev = null;

			foreach ($steps as $i => $s) {
				$count = (int)$s['count'];

				$overall = $base > 0 ? round(($count / $base) * 100, 2) : 0.0;
				$stepRate = ($prev !== null && $prev > 0) ? round(($count / $prev) * 100, 2) : 0.0;

				$steps[$i]['overall_percent'] = $overall;     // % din registered
				$steps[$i]['step_percent'] = $i === 0 ? 100.0 : $stepRate; // % din pasul anterior

				$prev = $count;
			}

			return $steps;
		}
	}
