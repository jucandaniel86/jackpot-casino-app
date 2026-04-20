<?php

	namespace App\Repositories\Stats\Services;

	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Models\Bet;
	use App\Repositories\Stats\Contracts\GamesTabServiceInterface;
	use Carbon\Carbon;
	use Illuminate\Support\Facades\DB;

	class GamesTabService implements GamesTabServiceInterface
	{
		public function summary(array $filters = []): array
		{
			$to = !empty($filters['to']) ? Carbon::parse($filters['to']) : Carbon::now();
			$from = !empty($filters['from']) ? Carbon::parse($filters['from']) : (clone $to)->subDays(7);

			$currencyCode = $filters['currency_code'] ?? null; // ex PEP
			$displayCurrency = $currencyCode ? strtoupper((string)$currencyCode) : (string)config('crypto.defaultCurrency', 'SOLANA:PEP');
			$uiDecimals = CurrencyDecimals::uiForCurrency($displayCurrency);
			$providerId = $filters['provider_id'] ?? null;
			$casinoId = $filters['int_casino_id'] ?? null;
			$limit = (int)($filters['limit'] ?? 10);
			$limit = max(1, min($limit, 100));

			$orderBy = $filters['order_by'] ?? 'bets'; // bets|wagered
			if (!in_array($orderBy, ['bets', 'wagered'], true)) {
				$orderBy = 'bets';
			}

			$q = Bet::query()
				->from('bets as b')
				->join('games as g', 'g.game_id', '=', 'b.game_id')
				->where('b.when_placed', '>=', $from)
				->where('b.when_placed', '<', $to);

			if ($currencyCode) {
				$q->where('b.currency', strtoupper($currencyCode));
			}

			if ($providerId !== null && $providerId !== '') {
				$q->where('g.provider_id', (int)$providerId);
			}

			if ($casinoId !== null && $casinoId !== '') {
				$q->where('b.int_casino_id', '=', $casinoId);
			}

			// Un singur query: bets_count, players_count, wagered, won, refunded
			$rows = $q->groupBy('b.game_id', 'g.name', 'g.provider_id')
				->get([
					'b.game_id',
					'g.name as game_name',
					'g.provider_id',

					// cât de “jucat”
					DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type='bet' THEN 1 ELSE 0 END),0) AS bets_count"),
					DB::raw("COUNT(DISTINCT CASE WHEN b.transaction_type='bet' THEN b.user_id ELSE NULL END) AS players_count"),

					// totals
					DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type='bet' THEN b.stake ELSE 0 END),0) AS wagered"),
					DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type='win' THEN b.payout ELSE 0 END),0) AS won"),
					DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type='refund' THEN b.payout ELSE 0 END),0) AS refunded"),
				]);

			// sortare în PHP (simplu) sau în SQL (mai rapid).
			// Prefer SQL: refacem orderBy și limit în query ca să nu returnezi toate jocurile.
			// Dar ca să o faci strict în SQL, trebuie să pui ORDER BY după alias.
			// MySQL permite ORDER BY alias; facem direct:
			$q2 = Bet::query()
				->from('bets as b')
				->join('games as g', 'g.game_id', '=', 'b.game_id')
				->where('b.when_placed', '>=', $from)
				->where('b.when_placed', '<', $to);

			if ($currencyCode) {
				$q2->where('b.currency', strtoupper($currencyCode));
			}
			if ($providerId !== null && $providerId !== '') {
				$q2->where('g.provider_id', (int)$providerId);
			}
			if ($casinoId !== null && $casinoId !== '') {
				$q2->where('b.int_casino_id', '=', $casinoId);
			}

			$rows = $q2->groupBy('b.game_id', 'g.name', 'g.provider_id')
				->orderByDesc($orderBy === 'wagered' ? 'wagered' : 'bets_count')
				->limit($limit)
				->get([
					'b.game_id',
					'g.name as game_name',
					'g.provider_id',
					DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type='bet' THEN 1 ELSE 0 END),0) AS bets_count"),
					DB::raw("COUNT(DISTINCT CASE WHEN b.transaction_type='bet' THEN b.user_id ELSE NULL END) AS players_count"),
					DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type='bet' THEN b.stake ELSE 0 END),0) AS wagered"),
					DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type='win' THEN b.payout ELSE 0 END),0) AS won"),
					DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type='refund' THEN b.payout ELSE 0 END),0) AS refunded"),
				]);

			$items = [];
			foreach ($rows as $r) {
				$wageredRaw = (string)$r->wagered;
				$wonRaw = (string)$r->won;
				$refundedRaw = (string)$r->refunded;
				$wagered = $this->formatCurrencyValue($wageredRaw, $uiDecimals);
				$won = $this->formatCurrencyValue($wonRaw, $uiDecimals);
				$refunded = $this->formatCurrencyValue($refundedRaw, $uiDecimals);

				// net won includes refunds? pentru RTP de obicei: payouts (wins) / wagered.
				// Refund e separat; dacă vrei RTP “strict”, ignoră refund.
				// Eu îți dau ambele:
				$rtp = $this->percent($wonRaw, $wageredRaw, 2);
				$rtpNet = $this->percent(bcadd($wonRaw, $refundedRaw, 8), $wageredRaw, 2);

				$items[] = [
					'game_id' => (int)$r->game_id,
					'game_name' => $r->game_name,
					'provider_id' => (int)$r->provider_id,
					'bets_count' => (int)$r->bets_count,
					'players_count' => (int)$r->players_count,
					'wagered' => $wagered,
					'won' => $won,
					'refunded' => $refunded,
					'rtp_percent' => $rtp,         // wins / wagered * 100
					'rtp_net_percent' => $rtpNet,  // (wins+refunds)/wagered*100 (opțional)
				];
			}

			return [
				'range' => [
					'from' => $from->toISOString(),
					'to' => $to->toISOString(),
				],
				'filters' => [
					'currency_code' => $currencyCode ? strtoupper($currencyCode) : null,
					'provider_id' => $providerId !== null && $providerId !== '' ? (int)$providerId : null,
					'limit' => $limit,
					'order_by' => $orderBy,
				],
				'items' => $items,
			];
		}

		private function percent(string $part, string $total, int $scale = 2): string
		{
			if (bccomp($total, '0', 8) !== 1) {
				return number_format(0, $scale, '.', '');
			}

			$ratio = bcdiv($part, $total, 18);
			return bcmul($ratio, '100', $scale);
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
