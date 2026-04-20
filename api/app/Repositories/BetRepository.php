<?php

	namespace App\Repositories;

	use App\Interfaces\BetInterface;
	use App\Models\Bet;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;

	class BetRepository implements BetInterface
	{
		/**
		 * @param string $date
		 * @return string
		 */
		protected function formatDateForSQL(string $date): string
		{
			return date("Y-m-d H:i:s", strtotime($date));
		}

		/**
		 * @param $searchJSON
		 * @return false|string
		 */
		protected function handleSearch($searchJSON)
		{
			$searchOptions = json_decode($searchJSON);
			$returnSQL = false;

			if (isset($searchOptions->item) && (string)$searchOptions->item !== "" && isset($searchOptions->column)) {
				switch ($searchOptions->column) {
					case "game":
						$returnSQL = "(games.name LIKE '%{$searchOptions->item}%' OR games.game_id LIKE '%{$searchOptions->item}%')";
						break;
					case "round":
						$returnSQL = "(operator_round_id LIKE '%{$searchOptions->item}%')";
						break;
					case "session":
						$returnSQL = "(session_id LIKE '%{$searchOptions->item}%')";
						break;
					case "user":
						$returnSQL = "(players.username LIKE '%{$searchOptions->item}%' OR players.fixed_id LIKE '%{$searchOptions->item}%' OR players.email LIKE '%{$searchOptions->item}%')";
						break;
					case "transaction":
						$returnSQL = "(operator_transaction_id LIKE '%{$searchOptions->item}%' OR transaction_id LIKE '%{$searchOptions->item}%')";
						break;
				}
			}

			return $returnSQL;
		}

		/**
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function search(Request $request): array
		{
			//pagination var
			$start = $request->has('start') ? $request->get('start') : 0;
			$length = $request->has('length') ? $request->get('length') : 50;
			$offset = ($start - 1) * $length;

			//select
			$select = "*";
			$groupBy = "";
			$search = false;
			if ($request->has('report')) {
				switch ($request->get('report')) {
					case 'transactions':
						$select = "transaction_id, session_id, operator_transaction_id, players.username, players.fixed_id, operator_round_id,  stake, payout, when_placed, currency, games.name as game_name, transaction_type";
						break;
					case 'games':
						$select = 'bets.game_id, bets.currency, SUM(stake) as total_stake, SUM(payout) as total_payout, COUNT(bets.id) as total_bets, games.name as game_name, 0 as ggr';
						$groupBy = 'bets.game_id, bets.currency';
						break;
					case 'users':
						$select = 'bets.user_id, bets.currency, SUM(stake) as total_stake, SUM(payout) as total_payout, COUNT(bets.id) as total_bets, players.username, players.fixed_id, 0 as ggr';
						$groupBy = 'bets.user_id, bets.currency';
						break;
					case 'session':
						$select = 'bets.session_id, bets.currency, SUM(stake) as total_stake, SUM(payout) as total_payout, COUNT(bets.id) as total_bets, players.username, players.fixed_id, 0 as ggr';
						$groupBy = 'bets.session_id, bets.currency';
						break;
				}
			}

			if ($request->has('search')) {
				$search = $this->handleSearch($request->get('search'));
//				return [
//					'search' => $search
//				];
			}

			$ResultsData = Bet::query()
				->selectRaw($select)
				->when($request->has('fromDate') && $request->has('toDate'), function ($query) use ($request) {
					$query->whereRaw('(when_placed >= "' . $this->formatDateForSQL($request->get('fromDate')) . '" AND when_placed <= "' . $this->formatDateForSQL($request->get('toDate')) . '")');
				})
				->when($request->filled('int_casino_id'), function ($query) use ($request) {
					$query->where('bets.int_casino_id', $request->get('int_casino_id'));
				})
				->when($request->has('currency') && (string)$request->get('currency') !== "", function ($query) use ($request) {
					$query->where('currency', $request->get('currency'));
				})
				->when($search, function ($query) use ($search) {
					$query->whereRaw($search);
				})
				->join('players', 'bets.user_id', '=', 'players.id')
				->join('games', 'bets.game_id', '=', 'games.game_id')
				->orderBy('when_placed', 'DESC');

			if ($groupBy != "") {
				$ResultsData->groupByRaw($groupBy);
			}

			return [
				"success" => true,
				'total' => $ResultsData->get()->count(),
				'items' => $ResultsData->limit($length)
					->offset($offset)
					->get(),

			];
		}
	}
