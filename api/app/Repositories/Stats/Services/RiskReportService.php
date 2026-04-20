<?php

	namespace App\Repositories\Stats\Services;

	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Models\Bet;
	use App\Repositories\Stats\Contracts\RiskReportServiceInterface;
	use Carbon\Carbon;
	use Illuminate\Support\Facades\DB;

	class RiskReportService implements RiskReportServiceInterface
	{
		public function players(array $filters): array
		{
			$from = Carbon::parse($filters['from']);
			$to = Carbon::parse($filters['to']);

			$currency = $filters['currency_code'] ?? null; // ex PEP
			$uiDecimals = $this->currencyUiDecimals($currency);
			$minBets = (int)($filters['min_bets'] ?? 30);
			$minWagered = (string)($filters['min_wagered'] ?? '10.00000000'); // decimal(64,8)
			$limit = (int)($filters['limit'] ?? 50);
			$limit = max(1, min($limit, 200));
			$casinoID = $filters['int_casino_id'] ?? null;

			// Agregare per user: bets_count, wagered, won, refunded, profit
			$q = Bet::query()
				->from('bets as b')
				->join('players as p', 'p.id', '=', 'b.user_id')
				->where('b.when_placed', '>=', $from)
				->where('b.when_placed', '<', $to);

			if ($currency) {
				$q->where('b.currency', strtoupper($currency));
			}

			if ($casinoID) {
				$q->where('b.int_casino_id', '=', $casinoID);
			}

			// bets_count: doar tranzacțiile BET (nu win/refund)
			// wagered: sum stake pe bet
			// won: sum payout pe win
			// refunded: sum payout pe refund (la tine refund row creditează payout)
			$rows = $q->groupBy('b.user_id', 'p.username')
				->havingRaw("COUNT(CASE WHEN b.transaction_type='bet' THEN 1 END) >= ?", [$minBets])
				->havingRaw("COALESCE(SUM(CASE WHEN b.transaction_type='bet' THEN b.stake ELSE 0 END),0) >= ?", [$minWagered])
				->orderByDesc('profit')
				->limit($limit)
				->get([
					'b.user_id',
					'p.username as username',

					DB::raw("COUNT(CASE WHEN b.transaction_type='bet' THEN 1 END) AS bets_count"),
					DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type='bet' THEN b.stake ELSE 0 END),0) AS wagered"),
					DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type='win' THEN b.payout ELSE 0 END),0) AS won"),
					DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type='refund' THEN b.payout ELSE 0 END),0) AS refunded"),

					// profit = won + refunded - wagered
					DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type IN ('win','refund') THEN b.payout ELSE 0 END),0) - 
                         COALESCE(SUM(CASE WHEN b.transaction_type='bet' THEN b.stake ELSE 0 END),0) AS profit"),
				]);

			$items = [];
			foreach ($rows as $r) {
				$wagered = $this->formatCurrencyValue((string)$r->wagered, $uiDecimals);
				$won = $this->formatCurrencyValue((string)$r->won, $uiDecimals);
				$refunded = $this->formatCurrencyValue((string)$r->refunded, $uiDecimals);
				$profit = $this->formatCurrencyValue((string)$r->profit, $uiDecimals);

				// ratios
				$winRate = $this->percent($won, $wagered, 2);           // won/wagered
				$refundRate = $this->percent($refunded, $wagered, 2);   // refunded/wagered
				$profitRate = $this->percent($profit, $wagered, 2);     // profit/wagered (poate fi negativ)

				// scor simplu (heuristic)
				$riskScore = $this->riskScore($winRate, $refundRate, $profitRate, (int)$r->bets_count);

				$items[] = [
					'user_id' => (int)$r->user_id,
					'username' => $r->username,
					'bets_count' => (int)$r->bets_count,
					'wagered' => $wagered,
					'won' => $won,
					'refunded' => $refunded,
					'profit' => $profit,

					'win_rate_percent' => $winRate,
					'refund_rate_percent' => $refundRate,
					'profit_rate_percent' => $profitRate,

					'risk_score' => $riskScore,
					'risk_reasons' => $this->riskReasons($winRate, $refundRate, $profitRate),
				];
			}

			return [
				'range' => ['from' => $from->toISOString(), 'to' => $to->toISOString()],
				'filters' => [
					'currency_code' => $currency ? strtoupper($currency) : null,
					'min_bets' => $minBets,
					'min_wagered' => $this->formatCurrencyValue($minWagered, $uiDecimals),
					'limit' => $limit,
				],
				'players' => $items,
			];
		}

		public function duplicates(array $filters): array
		{
			$from = Carbon::parse($filters['from']);
			$to = Carbon::parse($filters['to']);
			$currency = $filters['currency_code'] ?? null;
			$limit = (int)($filters['limit'] ?? 200);
			$limit = max(1, min($limit, 500));
			$casinoID = $filters['int_casino_id'] ?? null;

			// Duplicate operator_transaction_id pe același tip (BET/WIN/REFUND) în interval.
			// Asta arată retries sau bug/abuse.
			$q = Bet::query()
				->from('bets as b')
				->whereNotNull('b.operator_transaction_id')
				->where('b.operator_transaction_id', '<>', '')
				->where('b.when_placed', '>=', $from)
				->where('b.when_placed', '<', $to);

			if ($currency) {
				$q->where('b.currency', strtoupper($currency));
			}

			if ($casinoID) {
				$q->where('b.int_casino_id', '=', $casinoID);
			}

			$rows = $q->groupBy('b.transaction_type', 'b.operator_transaction_id')
				->havingRaw('COUNT(*) > 1')
				->orderByDesc(DB::raw('COUNT(*)'))
				->limit($limit)
				->get([
					'b.transaction_type',
					'b.operator_transaction_id',
					DB::raw('COUNT(*) as cnt'),
					DB::raw('MIN(b.when_placed) as first_seen'),
					DB::raw('MAX(b.when_placed) as last_seen'),
				]);

			return [
				'range' => ['from' => $from->toISOString(), 'to' => $to->toISOString()],
				'filters' => [
					'currency_code' => $currency ? strtoupper($currency) : null,
					'limit' => $limit,
				],
				'duplicates' => $rows->map(fn($r) => [
					'transaction_type' => $r->transaction_type,
					'operator_transaction_id' => $r->operator_transaction_id,
					'count' => (int)$r->cnt,
					'first_seen' => Carbon::parse($r->first_seen)->toISOString(),
					'last_seen' => Carbon::parse($r->last_seen)->toISOString(),
				])->all(),
			];
		}

		private function percent(string $part, string $total, int $scale = 2): string
		{
			// total <= 0 => 0
			if (bccomp($total, '0', 8) !== 1) {
				return number_format(0, $scale, '.', '');
			}

			// allow negative part (profit can be negative)
			$ratio = bcdiv($part, $total, 18);
			return bcmul($ratio, '100', $scale);
		}

		private function riskScore(string $winRate, string $refundRate, string $profitRate, int $betsCount): int
		{
			// Heuristic simplu:
			// - profit_rate > 20% => suspect
			// - win_rate > 120% => suspect (includeți doar payout/wager; depinde de jocuri)
			// - refund_rate > 10% => suspect (refund e rar, de obicei)
			$score = 0;

			if ($betsCount >= 50) $score += 5;

			if ((float)$profitRate > 20) $score += 50;
			if ((float)$profitRate > 50) $score += 30;

			if ((float)$winRate > 120) $score += 25;
			if ((float)$winRate > 150) $score += 25;

			if ((float)$refundRate > 5) $score += 15;
			if ((float)$refundRate > 10) $score += 20;

			return min(100, $score);
		}

		private function riskReasons(string $winRate, string $refundRate, string $profitRate): array
		{
			$reasons = [];

			if ((float)$profitRate > 20) $reasons[] = 'High profit rate';
			if ((float)$winRate > 120) $reasons[] = 'High win rate';
			if ((float)$refundRate > 5) $reasons[] = 'High refund rate';

			return $reasons;
		}

		// === OVERVIEW (nou) ===
		public function overview(array $filters): array
		{
			$from = Carbon::parse($filters['from']);
			$to = Carbon::parse($filters['to']);

			$currency = $filters['currency_code'] ?? null; // ex: PEP
			$uiDecimals = $this->currencyUiDecimals($currency);

			$casinoID = $filters['int_casino_id'] ?? null;

			// praguri (le poți ajusta ușor)
			$minBets = (int)($filters['min_bets'] ?? 30);
			$minWagered = (string)($filters['min_wagered'] ?? '10.00000000');

			// 1) Subquery: agregare per user (stake/wins/refunds/profit)
			$base = Bet::query()
				->from('bets as b')
				->join('players as p', 'p.id', '=', 'b.user_id')
				->where('b.when_placed', '>=', $from)
				->where('b.when_placed', '<', $to);

			if ($currency) {
				$base->where('b.currency', strtoupper($currency));
			}

			if ($casinoID) {
				$base->where('b.int_casino_id', '=', $casinoID);
			}

			$perUser = $base
				->groupBy('b.user_id', 'p.username')
				->havingRaw("COUNT(CASE WHEN b.transaction_type='bet' THEN 1 END) >= ?", [$minBets])
				->havingRaw("COALESCE(SUM(CASE WHEN b.transaction_type='bet' THEN b.stake ELSE 0 END),0) >= ?", [$minWagered])
				->select([
					'b.user_id',
					'p.username as username',
					DB::raw("COUNT(CASE WHEN b.transaction_type='bet' THEN 1 END) AS bets_count"),
					DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type='bet' THEN b.stake ELSE 0 END),0) AS wagered"),
					DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type='win' THEN b.payout ELSE 0 END),0) AS won"),
					DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type='refund' THEN b.payout ELSE 0 END),0) AS refunded"),
					DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type IN ('win','refund') THEN b.payout ELSE 0 END),0) -
                         COALESCE(SUM(CASE WHEN b.transaction_type='bet' THEN b.stake ELSE 0 END),0) AS profit"),
				]);

			// 2) Top profit user
			$topProfit = DB::query()->fromSub($perUser, 'u')
				->orderByDesc('u.profit')
				->limit(1)
				->first(['u.user_id', 'u.username', 'u.profit', 'u.wagered']);

			// 3) Highest RTP player: won / wagered
			// (MySQL: avoid divide by zero)
			$highestRtp = DB::query()->fromSub($perUser, 'u')
				->orderByDesc(DB::raw("CASE WHEN u.wagered > 0 THEN (u.won / u.wagered) ELSE 0 END"))
				->limit(1)
				->first(['u.user_id', 'u.username', 'u.won', 'u.wagered']);

			// 4) High/Medium risk counts (heuristic simplă, consistentă cu UI)
			// Calculăm în SQL pe subquery:
			$riskCounts = DB::query()->fromSub($perUser, 'u')
				->selectRaw("
                SUM(
                  CASE
                    WHEN (u.wagered > 0 AND ((u.profit / u.wagered) * 100) > 20)
                      OR (u.wagered > 0 AND ((u.won / u.wagered) * 100) > 120)
                      OR (u.wagered > 0 AND ((u.refunded / u.wagered) * 100) > 5)
                    THEN 1 ELSE 0
                  END
                ) AS high_risk_players,
                SUM(
                  CASE
                    WHEN
                      NOT (
                        (u.wagered > 0 AND ((u.profit / u.wagered) * 100) > 20)
                        OR (u.wagered > 0 AND ((u.won / u.wagered) * 100) > 120)
                        OR (u.wagered > 0 AND ((u.refunded / u.wagered) * 100) > 5)
                      )
                      AND (
                        (u.wagered > 0 AND ((u.profit / u.wagered) * 100) > 10)
                        OR (u.wagered > 0 AND ((u.won / u.wagered) * 100) > 110)
                        OR (u.wagered > 0 AND ((u.refunded / u.wagered) * 100) > 2)
                      )
                    THEN 1 ELSE 0
                  END
                ) AS medium_risk_players
            ")
				->first();

			// 5) Duplicate tx (ultimele 24h în intervalul cerut sau separat)
			$dupFrom = Carbon::now()->subHours(24);
			$dupTo = Carbon::now();

			$dupQ = Bet::query()
				->from('bets as b')
				->whereNotNull('b.operator_transaction_id')
				->where('b.operator_transaction_id', '<>', '')
				->where('b.when_placed', '>=', $dupFrom)
				->where('b.when_placed', '<', $dupTo);

			if ($currency) {
				$dupQ->where('b.currency', strtoupper($currency));
			}

			if ($casinoID) {
				$dupQ->where('b.int_casino_id', '=', $casinoID);
			}

			// număr de “grupuri” duplicate (txid+type cu count>1)
			$duplicateGroups24h = $dupQ
				->groupBy('b.transaction_type', 'b.operator_transaction_id')
				->havingRaw('COUNT(*) > 1')
				->get([DB::raw('COUNT(*) as cnt')])
				->count();

			return [
				'range' => [
					'from' => $from->toISOString(),
					'to' => $to->toISOString(),
				],
				'filters' => [
					'currency_code' => $currency ? strtoupper($currency) : null,
					'min_bets' => $minBets,
					'min_wagered' => $this->formatCurrencyValue($minWagered, $uiDecimals),
				],
				'kpis' => [
					'high_risk_players' => (int)($riskCounts->high_risk_players ?? 0),
					'medium_risk_players' => (int)($riskCounts->medium_risk_players ?? 0),
					'duplicate_transactions_24h' => (int)$duplicateGroups24h,
					'top_profit_user' => $topProfit ? [
						'user_id' => (int)$topProfit->user_id,
						'username' => $topProfit->username,
						'profit' => $this->formatCurrencyValue((string)$topProfit->profit, $uiDecimals),
						'wagered' => $this->formatCurrencyValue((string)$topProfit->wagered, $uiDecimals),
					] : null,
					'highest_rtp_player' => $highestRtp ? [
						'user_id' => (int)$highestRtp->user_id,
						'username' => $highestRtp->username,
						'rtp_percent' => $this->percent((string)$highestRtp->won, (string)$highestRtp->wagered, 2),
					] : null,
				],
			];
		}

		// === GAME ABUSE (phase 2 MVP) ===
		public function gameAbuse(array $filters): array
		{
			$from = Carbon::parse($filters['from']);
			$to = Carbon::parse($filters['to']);
			$currency = $filters['currency_code'] ?? null;
			$uiDecimals = $this->currencyUiDecimals($currency);
			$casinoID = $filters['int_casino_id'] ?? null;
			$limit = (int)($filters['limit'] ?? 50);
			$limit = max(1, min($limit, 200));

			$q = Bet::query()
				->from('bets as b')
				->join('games as g', 'g.game_id', '=', 'b.game_id')
				->where('b.when_placed', '>=', $from)
				->where('b.when_placed', '<', $to);

			if ($currency) {
				$q->where('b.currency', strtoupper($currency));
			}

			if ($casinoID) {
				$q->where('b.int_casino_id', '=', $casinoID);
			}

			$rows = $q->groupBy('b.game_id', 'g.name', 'g.provider_id')
				->orderByDesc('wagered')
				->limit($limit)
				->get([
					'b.game_id',
					'g.name as game_name',
					'g.provider_id',
					DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type='bet' THEN b.stake ELSE 0 END),0) AS wagered"),
					DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type='win' THEN b.payout ELSE 0 END),0) AS won"),
					DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type='refund' THEN b.payout ELSE 0 END),0) AS refunded"),
					DB::raw("COUNT(DISTINCT CASE WHEN b.transaction_type='bet' THEN b.user_id ELSE NULL END) AS players_count"),
				]);

			$items = [];
			foreach ($rows as $r) {
				$wagered = $this->formatCurrencyValue((string)$r->wagered, $uiDecimals);
				$won = $this->formatCurrencyValue((string)$r->won, $uiDecimals);
				$refunded = $this->formatCurrencyValue((string)$r->refunded, $uiDecimals);

				$rtp = $this->percent($won, $wagered, 2);
				$refundRate = $this->percent($refunded, $wagered, 2);

				// Flag simplu
				$flag = 'ok';
				if ((float)$rtp > 120 || (float)$refundRate > 5) $flag = 'warning';
				if ((float)$rtp > 140 || (float)$refundRate > 10) $flag = 'critical';

				$items[] = [
					'game_id' => (int)$r->game_id,
					'game_name' => $r->game_name,
					'provider_id' => (int)$r->provider_id,
					'players_count' => (int)$r->players_count,
					'wagered' => $wagered,
					'won' => $won,
					'refunded' => $refunded,
					'rtp_percent' => $rtp,
					'refund_rate_percent' => $refundRate,
					'flag' => $flag,
				];
			}

			return [
				'range' => ['from' => $from->toISOString(), 'to' => $to->toISOString()],
				'filters' => [
					'currency_code' => $currency ? strtoupper($currency) : null,
					'limit' => $limit,
				],
				'items' => $items,
			];
		}

		private function currencyUiDecimals(?string $currencyCode): int
		{
			if (!$currencyCode) {
				return 0;
			}

			return CurrencyDecimals::uiForCurrency(strtoupper($currencyCode));
		}

		private function formatCurrencyValue(string $value, int $uiDecimals): string
		{
			$value = trim($value);
			if ($value === '') {
				return '0';
			}

			return bcadd($value, '0', max(0, $uiDecimals));
		}

		// === Ai deja players() și duplicates() la tine ===
		// Păstrează-le. (Nu le rescriu aici ca să nu îți stric implementarea.)

	}
