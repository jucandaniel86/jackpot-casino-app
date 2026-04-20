<?php

	namespace App\Repositories\Stats\Services;

	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Models\Bet;
	use App\Repositories\Stats\Contracts\GgrReportServiceInterface;
	use Carbon\Carbon;
	use Illuminate\Support\Facades\DB;

	class GgrReportService implements GgrReportServiceInterface
	{
		public function report(array $filters): array
		{
			$from = Carbon::parse($filters['from']);
			$to = Carbon::parse($filters['to']);

			$groupBy = $filters['group_by'] ?? 'currency';
			$currencyCode = $filters['currency_code'] ?? null; // ex PEP
			$providerId = $filters['provider_id'] ?? null;
			$gameId = $filters['game_id'] ?? null; // provider game_id

			$q = Bet::query()
				->from('bets as b')
				->where('b.when_placed', '>=', $from)
				->where('b.when_placed', '<', $to);

			if ($currencyCode) {
				$q->where('b.currency', strtoupper($currencyCode));
			}

			if ($gameId !== null && $gameId !== '') {
				$q->where('b.game_id', (int)$gameId);
			}

			$needsGamesJoin = in_array($groupBy, ['provider', 'game'], true) || ($providerId !== null && $providerId !== '');
			if ($needsGamesJoin) {
				$q->join('games as g', 'g.game_id', '=', 'b.game_id');
			}

			if ($providerId !== null && $providerId !== '') {
				$q->where('g.provider_id', (int)$providerId);
			}

			$select = [
				DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type='bet' THEN b.stake ELSE 0 END),0) AS wagered"),
				DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type='win' THEN b.payout ELSE 0 END),0) AS won"),
				// în implementarea ta refund-row creditează în payout
				DB::raw("COALESCE(SUM(CASE WHEN b.transaction_type='refund' THEN b.payout ELSE 0 END),0) AS refunded"),
			];

			if ($groupBy === 'currency') {
				$q->groupBy('b.currency');
				array_unshift($select, 'b.currency as currency_code');
			} elseif ($groupBy === 'provider') {
				$q->groupBy('g.provider_id');
				array_unshift($select, 'g.provider_id');
			} elseif ($groupBy === 'game') {
				$q->groupBy('b.game_id', 'g.name', 'g.provider_id');
				array_unshift($select, 'b.game_id', 'g.name as game_name', 'g.provider_id');
			} else {
				throw new \InvalidArgumentException("Invalid group_by: {$groupBy}");
			}

			$rows = $q->get($select);
			$defaultUiDecimals = CurrencyDecimals::uiForCurrency(
				$currencyCode ? strtoupper((string)$currencyCode) : (string)config('crypto.defaultCurrency', 'SOLANA:PEP')
			);

			$items = [];
			foreach ($rows as $r) {
				$wageredRaw = (string)$r->wagered;
				$wonRaw = (string)$r->won;
				$refundedRaw = (string)$r->refunded;

				// stake/payout sunt decimal(64,8) => scale=8
				$ggrRaw = bcsub(bcsub($wageredRaw, $wonRaw, 8), $refundedRaw, 8);
				$ngrRaw = $ggrRaw; // până ai bonusuri/fees
				$uiDecimals = $groupBy === 'currency'
					? CurrencyDecimals::uiForCurrency((string)$r->currency_code)
					: $defaultUiDecimals;

				$item = [
					'wagered' => $this->formatCurrencyValue($wageredRaw, $uiDecimals),
					'won' => $this->formatCurrencyValue($wonRaw, $uiDecimals),
					'refunded' => $this->formatCurrencyValue($refundedRaw, $uiDecimals),
					'ggr' => $this->formatCurrencyValue($ggrRaw, $uiDecimals),
					'ngr' => $this->formatCurrencyValue($ngrRaw, $uiDecimals),
				];

				if ($groupBy === 'currency') {
					$item['currency_code'] = $r->currency_code;
				} elseif ($groupBy === 'provider') {
					$item['provider_id'] = (int)$r->provider_id;
				} else { // game
					$item['game_id'] = (int)$r->game_id;
					$item['game_name'] = $r->game_name;
					$item['provider_id'] = (int)$r->provider_id;
				}

				$items[] = $item;
			}

			return [
				'range' => [
					'from' => $from->toISOString(),
					'to' => $to->toISOString(),
				],
				'filters' => [
					'group_by' => $groupBy,
					'currency_code' => $currencyCode ? strtoupper($currencyCode) : null,
					'provider_id' => $providerId !== null && $providerId !== '' ? (int)$providerId : null,
					'game_id' => $gameId !== null && $gameId !== '' ? (int)$gameId : null,
				],
				'items' => $items,
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
