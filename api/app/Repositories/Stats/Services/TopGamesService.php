<?php

	namespace App\Repositories\Stats\Services;

	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Models\Bet;
	use App\Repositories\Stats\Contracts\TopGamesServiceInterface;
	use Carbon\Carbon;
	use Illuminate\Support\Facades\DB;

	class TopGamesService implements TopGamesServiceInterface
	{
		public function topGames(array $filters = []): array
		{
			// defaults: today
			$from = !empty($filters['from']) ? Carbon::parse($filters['from']) : Carbon::today();
			$to = !empty($filters['to']) ? Carbon::parse($filters['to']) : Carbon::tomorrow();
			$casinoId = !empty($filters['int_casino_id']) ? $filters['int_casino_id'] : config('casino.defaultCasinoId');

			$currencyCode = $filters['currency_code'] ?? null; // ex: PEP
			$uiDecimals = CurrencyDecimals::uiForCurrency(
				$currencyCode ? strtoupper((string)$currencyCode) : (string)config('crypto.defaultCurrency', 'SOLANA:PEP')
			);
			$providerId = $filters['provider_id'] ?? null;
			$limit = (int)($filters['limit'] ?? 10);
			$limit = max(1, min($limit, 100));

			$q = Bet::query()
				->from('bets as b')
				->join('games as g', 'g.game_id', '=', 'b.game_id')
				->where('b.transaction_type', 'bet')
				->where('b.when_placed', '>=', $from)
				->where('b.when_placed', '<', $to)
				->where('b.int_casino_id', '=', $casinoId);

			if ($currencyCode) {
				$q->where('b.currency', strtoupper($currencyCode));
			}

			if ($providerId !== null && $providerId !== '') {
				$q->where('g.provider_id', (int)$providerId);
			}

			$rows = $q->groupBy('b.game_id', 'g.name', 'g.provider_id')
				->orderByDesc('bets_count')
				->limit($limit)
				->get([
					'b.game_id',
					'g.name as game_name',
					'g.provider_id',
					DB::raw('COUNT(*) as bets_count'),
					DB::raw('COUNT(DISTINCT b.user_id) as players_count'),
					DB::raw('COALESCE(SUM(b.stake),0) as wagered'),
				]);

			// opțional: RTP (won/wagered) pe interval, fără query extra
			// dacă vrei RTP, trebuie să includem și SUM(win.payout) într-un subquery sau CASE WHEN
			// îl adăugăm direct acum:
			// (l-am lăsat simplu; spune-mi dacă vrei RTP în top)

			return [
				'range' => [
					'from' => $from->toISOString(),
					'to' => $to->toISOString(),
				],
				'filters' => [
					'currency_code' => $currencyCode ? strtoupper($currencyCode) : null,
					'provider_id' => $providerId !== null && $providerId !== '' ? (int)$providerId : null,
					'limit' => $limit,
				],
				'items' => $rows->map(fn($r) => [
					'game_id' => (int)$r->game_id,
					'game_name' => $r->game_name,
					'provider_id' => (int)$r->provider_id,
					'bets_count' => (int)$r->bets_count,
					'players_count' => (int)$r->players_count,
					'wagered' => $this->formatCurrencyValue((string)$r->wagered, $uiDecimals),
				])->all(),
			];
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
