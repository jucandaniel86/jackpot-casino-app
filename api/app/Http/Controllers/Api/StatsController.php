<?php

	namespace App\Http\Controllers\Api;

	use App\Http\Controllers\Controller;
	use App\Repositories\Stats\Contracts\ConversionFunnelServiceInterface;
	use App\Repositories\Stats\Contracts\GamesTabServiceInterface;
	use App\Repositories\Stats\Contracts\GgrReportServiceInterface;
	use App\Repositories\Stats\Contracts\TopGamesServiceInterface;
	use Illuminate\Http\Request;
	use App\Repositories\Stats\Contracts\DashboardStatsServiceInterface;

	class StatsController extends Controller
	{
		/**
		 * @param Request $request
		 * @param DashboardStatsServiceInterface $svc
		 * @return array
		 */
		public function today(Request $request, DashboardStatsServiceInterface $svc)
		{
			$filters = $request->validate([
				'currency_id' => 'nullable|string|max:32',
				'currency_code' => 'nullable|string|max:16',
				'int_casino_id' => 'nullable|string',
				'from' => ['nullable', 'date'],
				'to' => ['nullable', 'date', 'after:from'],
			]);

			return $svc->today($filters);
		}

		/**
		 * @param Request $request
		 * @param TopGamesServiceInterface $svc
		 * @return array
		 */
		public function topGames(Request $request, TopGamesServiceInterface $svc)
		{
			$data = $request->validate([
				'from' => 'nullable|date',
				'to' => 'nullable|date',
				'currency_code' => 'nullable|string|max:16', // ex PEP
				'provider_id' => 'nullable|integer',
				'limit' => 'nullable|integer|min:1|max:100',
				'int_casino_id' => 'nullable|string'
			]);

			return $svc->topGames($data);
		}

		/**
		 * @param Request $request
		 * @param GgrReportServiceInterface $svc
		 * @return array
		 */
		public function ggr(Request $request, GgrReportServiceInterface $svc)
		{
			$data = $request->validate([
				'from' => 'required|date',
				'to' => 'required|date',
				'group_by' => 'nullable|in:currency,provider,game',
				'currency_code' => 'nullable|string|max:16', // ex PEP
				'provider_id' => 'nullable|integer',
				'game_id' => 'nullable|integer', // provider game_id
				'int_casino_id' => 'nullable|string',
			]);

			return $svc->report($data);
		}

		public function games(Request $request, GamesTabServiceInterface $svc)
		{
			$data = $request->validate([
				'from' => 'nullable|date',
				'to' => 'nullable|date',
				'currency_code' => 'nullable|string|max:16', // PEP
				'provider_id' => 'nullable|integer',
				'limit' => 'nullable|integer|min:1|max:100',
				'order_by' => 'nullable|in:bets,wagered',
				'int_casino_id' => 'nullable|string',
			]);

			return $svc->summary($data);
		}

		/**
		 * @param Request $request
		 * @param ConversionFunnelServiceInterface $svc
		 * @return array
		 */
		public function funnel(Request $request, ConversionFunnelServiceInterface $svc)
		{
			$data = $request->validate([
				'from' => 'required|date',
				'to' => 'required|date',
				'currency_code' => 'nullable|string|max:16', // PEP
				'bets_10_threshold' => 'nullable|integer|min:1|max:100000',
				'bets_100_threshold' => 'nullable|integer|min:1|max:100000',
				'int_casino_id' => 'nullable|string',
			]);

			return $svc->report($data);
		}
	}