<?php

	namespace App\Marketing\Services;

	use App\Marketing\Contracts\MarketingServiceInterface;
	use App\Marketing\Services\Queries\MarketingCohortsQuery;
	use App\Marketing\Services\Queries\MarketingGamesQuery;
	use App\Marketing\Services\Queries\MarketingOverviewQuery;
	use App\Marketing\Services\Queries\MarketingSegmentsQuery;
	use App\Marketing\Services\Queries\MarketingFunnelQuery;
	use Carbon\Carbon;

	class MarketingService implements MarketingServiceInterface
	{
		public function __construct(
			private MarketingOverviewQuery $overviewQ,
			private MarketingCohortsQuery  $cohortsQ,
			private MarketingGamesQuery    $gamesQ,
			private MarketingSegmentsQuery $segmentsQ,
			private MarketingFunnelQuery   $funnelQ,
		)
		{
		}

		public function overview(array $filters): array
		{
			[$from, $to, $currencyDb, $casinoId] = $this->normalizeFilters($filters);

			return [
				'range' => ['from' => $from->toISOString(), 'to' => $to->toISOString()],
				'currency_code' => $currencyDb,
				'cards' => $this->overviewQ->cards($from, $to, $currencyDb, $casinoId),
				'timeseries' => [
					'deposits_daily' => $this->overviewQ->timeseriesDaily($from, $to, $currencyDb, $casinoId),
					'ftd_daily' => $this->overviewQ->ftdTimeseriesDaily($from, $to, $currencyDb, $casinoId),
				],
			];
		}

		public function cohorts(array $filters): array
		{
			[$from, $to, $currencyDb, $casinoId] = $this->normalizeFilters($filters);

			// ex: by week cohorts + D1/D7/D30 retention
			return $this->cohortsQ->retention($from, $to, $currencyDb, $casinoId);
		}

		public function games(array $filters): array
		{
			[$from, $to, $currencyDb, $casinoId] = $this->normalizeFilters($filters);

			return [
				'top_ggr' => $this->gamesQ->topGgr($from, $to, $currencyDb, $casinoId, 20),
				'top_first_bet' => $this->gamesQ->topFirstBetGames($from, $to, $currencyDb, $casinoId, 20),
				'rtp' => $this->gamesQ->rtpPerGame($from, $to, $currencyDb, $casinoId, 50),
			];
		}

		public function segments(array $filters): array
		{
			[$from, $to, $currencyDb, $casinoId] = $this->normalizeFilters($filters);

			// segmentele nu depind mereu de from/to, dar e ok să le primească
			return $this->segmentsQ->overview($from, $to, $currencyDb, $casinoId, [
				'inactive_days' => (int)($filters['inactive_days'] ?? 7),
				'limit' => (int)($filters['limit'] ?? 100),
			]);
		}

		public function funnel(array $filters): array
		{
			[$from, $to, $currencyDb, $casinoId] = $this->normalizeFilters($filters);

			return [
				'range' => ['from' => $from->toISOString(), 'to' => $to->toISOString()],
				'result' => $this->funnelQ->build($from, $to, $currencyDb, $casinoId),
			];
		}

		private function normalizeFilters(array $filters): array
		{
			$from = Carbon::parse($filters['from']);
			$to = Carbon::parse($filters['to']);

			$currencyInput = strtoupper($filters['currency_code'] ?? 'PEP');
			$currencyDb = str_contains($currencyInput, ':') ? $currencyInput : "SOLANA:{$currencyInput}";
			$casinoId = $filters['int_casino_id'] ?? config('casino.defaultCasinoId');

			return [$from, $to, $currencyDb, $casinoId];
		}
	}