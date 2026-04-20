<?php

	namespace App\Marketing\Services\Queries;

	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Repositories\Crypto\Support\Money;
	use Carbon\Carbon;
	use Illuminate\Support\Facades\DB;

	class MarketingOverviewQuery
	{
		public function cards(Carbon $from, Carbon $to, string $currencyDb, string $casinoId): array
		{
			$uiDecimals = CurrencyDecimals::uiForCurrency($currencyDb);
			// Registrations
			$registrations = DB::table('players')
				->where('created_at', '>=', $from)
				->where('created_at', '<', $to)
				->count();

			// Active players (users who placed at least 1 bet)
			$activePlayers = DB::table('bets as b')
				->where('b.when_placed', '>=', $from)
				->where('b.when_placed', '<', $to)
				->where('b.transaction_type', 'bet')
				->distinct('b.user_id')
				->count('b.user_id');

			// Deposits count + sum(amount_base)
			$depositAgg = DB::table('transaction as t')
				->where('t.type', 'deposit')
				->where('t.status', 'confirmed')
				->where('t.currency', $currencyDb)
				->where('t.created_at', '>=', $from)
				->where('t.created_at', '<', $to)
				->where('t.int_casino_id', '=', $casinoId)
				->selectRaw("COUNT(*) as cnt, COALESCE(SUM(t.amount_base),0) as sum_base")
				->first();

			// Withdrawals count + sum(amount_base)
			$withdrawAgg = DB::table('transaction as t')
				->where('t.type', 'withdraw')
				->where('t.status', 'confirmed')
				->where('t.currency', $currencyDb)
				->where('t.created_at', '>=', $from)
				->where('t.created_at', '<', $to)
				->where('t.int_casino_id', '=', $casinoId)
				->selectRaw("COUNT(*) as cnt, COALESCE(SUM(t.amount_base),0) as sum_base")
				->first();

			// GGR = sum(stake) - sum(payout) (pe bets)
			$ggrAgg = DB::table('bets as b')
				->where('b.when_placed', '>=', $from)
				->where('b.when_placed', '<', $to)
				->where('b.currency', $this->symbolFromDbCurrency($currencyDb))
				->where('b.int_casino_id', '=', $casinoId)
				->selectRaw("COALESCE(SUM(b.stake),0) as stake_sum, COALESCE(SUM(b.payout),0) as payout_sum")
				->first();

			$stakeSum = (string)($ggrAgg->stake_sum ?? '0');
			$payoutSum = (string)($ggrAgg->payout_sum ?? '0');
			$ggr = $this->formatCurrencyValue((string)bcsub($stakeSum, $payoutSum, 8), $uiDecimals);

			// FTD = users who have first deposit in interval
			$ftd = DB::table('transaction as t')
				->join('wallets as w', 'w.id', '=', 't.wallet_id')
				->where('t.type', 'deposit')
				->where('t.status', 'confirmed')
				->where('t.currency', $currencyDb)
				->where('w.holder_type', 'App\\Models\\Player')
				->where('t.int_casino_id', '=', $casinoId)
				->select('w.holder_id')
				->groupBy('w.holder_id')
				->havingRaw('MIN(t.created_at) >= ? AND MIN(t.created_at) < ?', [$from, $to])
				->get()
				->count();

			$depositCount = (int)($depositAgg->cnt ?? 0);
			$withdrawCount = (int)($withdrawAgg->cnt ?? 0);
			$internalDecimals = CurrencyDecimals::internalForCurrency($currencyDb);
			$depositsSumBase = (string)($depositAgg->sum_base ?? '0');
			$withdrawalsSumBase = (string)($withdrawAgg->sum_base ?? '0');

			return [
				'registrations' => $registrations,
				'ftd' => $ftd,
				'active_players' => $activePlayers,
				'deposits' => [
					'count' => $depositCount,
					'sum_base' => Money::baseToUi($depositsSumBase, $internalDecimals, $uiDecimals),
					'decimals' => $internalDecimals,
					'ui_decimals' => $uiDecimals,
				],
				'withdrawals' => [
					'count' => $withdrawCount,
					'sum_base' => Money::baseToUi($withdrawalsSumBase, $internalDecimals, $uiDecimals),
					'decimals' => $internalDecimals,
					'ui_decimals' => $uiDecimals,
				],
				'ggr' => $ggr, // UI îl afișezi direct (bets table e deja decimal ui)
			];
		}

		public function timeseriesDaily(Carbon $from, Carbon $to, string $currencyDb, string $casinoId): array
		{
			$uiDecimals = CurrencyDecimals::uiForCurrency($currencyDb);
			// deposits/day
			$rows = DB::table('transaction as t')
				->where('t.type', 'deposit')
				->where('t.status', 'confirmed')
				->where('t.currency', $currencyDb)
				->where('t.created_at', '>=', $from)
				->where('t.created_at', '<', $to)
				->where('t.int_casino_id', '=', $casinoId)
				->selectRaw("DATE(t.created_at) as d, COUNT(*) as cnt, COALESCE(SUM(t.amount_base),0) as sum_base")
				->groupBy('d')
				->orderBy('d')
				->get();

			return $rows->map(fn($r) => [
				'date' => $r->d,
				'count' => (int)$r->cnt,
				'sum_base' => Money::baseToUi(
					(string)$r->sum_base,
					CurrencyDecimals::internalForCurrency($currencyDb),
					$uiDecimals
				),
				'decimals' => CurrencyDecimals::internalForCurrency($currencyDb),
				'ui_decimals' => $uiDecimals,
			])->toArray();
		}

		private function symbolFromDbCurrency(string $dbCurrency): string
		{
			return str_contains($dbCurrency, ':') ? explode(':', $dbCurrency, 2)[1] : $dbCurrency;
		}

		private function formatCurrencyValue(string $value, int $uiDecimals): string
		{
			return bcadd($value, '0', max(0, $uiDecimals));
		}

		public function ftdTimeseriesDaily(Carbon $from, Carbon $to, string $currencyDb, string $casinoId): array
		{
			// 1) Subquery: first deposit timestamp per user (lifetime) for this currency
			$firstDepositPerUser = DB::table('transaction as t')
				->join('wallets as w', 'w.id', '=', 't.wallet_id')
				->where('t.type', 'deposit')
				->where('t.status', 'confirmed')
				->where('t.int_casino_id', '=', $casinoId)
				->where('t.currency', $currencyDb)
				->where('w.holder_type', 'App\\Models\\Player')
				->groupBy('w.holder_id')
				->selectRaw('w.holder_id as user_id, MIN(t.created_at) as first_deposit_at');

			// 2) Outer query: filter first_deposit_at in [from,to) and group by day
			$rows = DB::query()
				->fromSub($firstDepositPerUser, 'fd')
				->where('fd.first_deposit_at', '>=', $from)
				->where('fd.first_deposit_at', '<', $to)
				->selectRaw('DATE(fd.first_deposit_at) as d, COUNT(*) as ftd_count')
				->groupBy('d')
				->orderBy('d')
				->get();

			return $rows->map(fn($r) => [
				'date' => $r->d,
				'ftd_count' => (int)$r->ftd_count,
			])->toArray();
		}

	}
