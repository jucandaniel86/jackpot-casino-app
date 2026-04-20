<?php

	namespace App\Marketing\Services\Queries;

	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use Carbon\Carbon;
	use Illuminate\Support\Facades\DB;

	class MarketingGamesQuery
	{
		/**
		 * Top games by GGR = SUM(stake) - SUM(payout)
		 * bets.currency uses symbol (PEP), not SOLANA:PEP.
		 */
		public function topGgr(Carbon $from, Carbon $to, string $currencyDb, string $casinoId, int $limit = 20): array
		{
			$symbol = $this->symbolFromDbCurrency($currencyDb);
			$uiDecimals = CurrencyDecimals::uiForCurrency($currencyDb);

			$rows = DB::table('bets as b')
				->join('games as g', 'g.game_id', '=', 'b.game_id')
				->where('b.when_placed', '>=', $from)
				->where('b.when_placed', '<', $to)
				->where('b.currency', $symbol)
				->where('b.int_casino_id', $casinoId)
				->selectRaw("
                g.id as game_pk,
                g.game_id as provider_game_id,
                g.name as game_name,
                COUNT(CASE WHEN b.transaction_type='bet' THEN 1 END) as bets_count,
                COALESCE(SUM(b.stake),0) as stake_sum,
                COALESCE(SUM(b.payout),0) as payout_sum,
                (COALESCE(SUM(b.stake),0) - COALESCE(SUM(b.payout),0)) as ggr
            ")
				->groupBy('g.id', 'g.game_id', 'g.name')
				->orderByDesc('ggr')
				->limit($limit)
				->get();

			return $rows->map(fn($r) => [
				'game_id' => (int)$r->game_pk,
				'provider_game_id' => (int)($r->provider_game_id ?? 0),
				'name' => (string)$r->game_name,
				'bets_count' => (int)$r->bets_count,
				'stake_sum' => $this->formatCurrencyValue((string)$r->stake_sum, $uiDecimals),
				'payout_sum' => $this->formatCurrencyValue((string)$r->payout_sum, $uiDecimals),
				'ggr' => $this->formatCurrencyValue((string)$r->ggr, $uiDecimals),
			])->toArray();
		}

		/**
		 * Top games by "first bet" count:
		 * For each user, find their first BET in interval and group by game.
		 */
		public function topFirstBetGames(Carbon $from, Carbon $to, string $currencyDb, string $casinoId, int $limit = 20): array
		{
			$symbol = $this->symbolFromDbCurrency($currencyDb);

			// subquery: first bet time per user in range
			$firstBetPerUser = DB::table('bets as b')
				->where('b.transaction_type', 'bet')
				->where('b.currency', $symbol)
				->where('b.int_casino_id', $casinoId)
				->where('b.when_placed', '>=', $from)
				->where('b.when_placed', '<', $to)
				->selectRaw('b.user_id, MIN(b.when_placed) as first_time')
				->groupBy('b.user_id');

			// join back to bets to get game_id for that first bet
			$rows = DB::query()
				->fromSub($firstBetPerUser, 'fb')
				->join('bets as b', function ($j) {
					$j->on('b.user_id', '=', 'fb.user_id')
						->on('b.when_placed', '=', 'fb.first_time');
				})
				->join('games as g', 'g.game_id', '=', 'b.game_id')
				->selectRaw("
                g.id as game_pk,
                g.game_id as provider_game_id,
                g.name as game_name,
                COUNT(*) as first_bet_users
            ")
				->groupBy('g.id', 'g.game_id', 'g.name')
				->orderByDesc('first_bet_users')
				->limit($limit)
				->get();

			return $rows->map(fn($r) => [
				'game_id' => (int)$r->game_pk,
				'provider_game_id' => (int)($r->provider_game_id ?? 0),
				'name' => (string)$r->game_name,
				'first_bet_users' => (int)$r->first_bet_users,
			])->toArray();
		}

		/**
		 * RTP per game = payout / stake (only if stake > 0).
		 * Useful to spot outliers; marketing uses it for “what games are too generous / too harsh”.
		 */
		public function rtpPerGame(Carbon $from, Carbon $to, string $currencyDb, string $casinoId, int $limit = 50): array
		{
			$symbol = $this->symbolFromDbCurrency($currencyDb);
			$uiDecimals = CurrencyDecimals::uiForCurrency($currencyDb);

			$rows = DB::table('bets as b')
				->join('games as g', 'g.game_id', '=', 'b.game_id')
				->where('b.currency', $symbol)
				->where('b.int_casino_id', $casinoId)
				->where('b.when_placed', '>=', $from)
				->where('b.when_placed', '<', $to)
				->selectRaw("
                g.id as game_pk,
                g.game_id as provider_game_id,
                g.name as game_name,
                COALESCE(SUM(b.stake),0) as stake_sum,
                COALESCE(SUM(b.payout),0) as payout_sum
            ")
				->groupBy('g.id', 'g.game_id', 'g.name')
				->orderByDesc('stake_sum')
				->limit($limit)
				->get();

			return $rows->map(function ($r) use ($uiDecimals) {
				$stake = (float)$r->stake_sum;
				$payout = (float)$r->payout_sum;
				$rtp = $stake > 0 ? ($payout / $stake) : 0.0;

				return [
					'game_id' => (int)$r->game_pk,
					'provider_game_id' => (int)($r->provider_game_id ?? 0),
					'name' => (string)$r->game_name,
					'stake_sum' => $this->formatCurrencyValue((string)$r->stake_sum, $uiDecimals),
					'payout_sum' => $this->formatCurrencyValue((string)$r->payout_sum, $uiDecimals),
					'rtp' => $rtp, // numeric
				];
			})->toArray();
		}

		private function symbolFromDbCurrency(string $dbCurrency): string
		{
			return str_contains($dbCurrency, ':') ? explode(':', $dbCurrency, 2)[1] : $dbCurrency;
		}

		private function formatCurrencyValue(string $value, int $uiDecimals): string
		{
			$value = trim($value);
			if ($value === '') {
				return '0';
			}

			return bcadd($value, '0', max(0, $uiDecimals));
		}
	}
