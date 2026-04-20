<?php

	namespace App\Repositories;

	use App\Http\Resources\GameResource;
	use App\Http\Resources\ProviderResource;
	use App\Interfaces\SearchInterface;
	use App\Models\Categories;
	use App\Models\Game;
	use App\Models\Providers;

	class SearchRepository implements SearchInterface
	{
		const SEARCH_LIMIT_RESULT = 30;

		/**
		 * @param string $searchTerm
		 * @return array
		 */
		public function search(array $filters = []): array
		{
			$CasinoID = $filters['casino_id'] ?? null;

			$Games = Game::query()
				->where('name', 'like', "%{$filters['search']}%")
				->when($CasinoID, function ($query) use ($CasinoID) {
					$query->whereHas('casinos', function ($q) use ($CasinoID) {
						$q->where('casinos.int_casino_id', $CasinoID);
					});
				})
				->limit(self::SEARCH_LIMIT_RESULT)
				->get();
//			$Providers = Providers::query()->where('name', 'like', "%{$searchTerm}%")->limit(self::SEARCH_LIMIT_RESULT)->get();
//			$Categories = Categories::query()->where('name', 'like', "%{$searchTerm}%")->limit(self::SEARCH_LIMIT_RESULT)->get();

			return [
				'games' => GameResource::collection($Games),
				'categories' => [],
				'provides' => []
			];
		}

		/**
		 * @param string $searchTerm
		 * @return array
		 */
		public function games(array $filters = []): array
		{
      $CasinoID = $filters['casino_id'] ?? null;
			$Games = Game::query()->selectRaw('name, game_id as id')
        ->where('name', 'like', "%{$filters['search']}%")
        ->when($CasinoID, function ($query) use ($CasinoID) {
          $query->whereHas('casinos', function ($q) use ($CasinoID) {
            $q->where('casinos.int_casino_id', $CasinoID);
          });
        })
        ->limit(self::SEARCH_LIMIT_RESULT)->get();
			return [
				'games' => $Games,
			];
		}
	}
