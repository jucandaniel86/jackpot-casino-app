<?php

	namespace App\Repositories;

	use App\Exceptions\ApiResponseException;
	use App\Interfaces\GameInterface;
	use App\Models\Game;
	use App\Models\Sections;
	use App\Traits\QueryTrait;
	use App\Traits\UploadFilesTrait;
	use Illuminate\Database\QueryException;
	use Illuminate\Support\Str;
	use Illuminate\Http\Request;

	class GameRepository implements GameInterface
	{
		use QueryTrait, UploadFilesTrait;

		public function list(array $params = []): array
		{
			$start = (isset($params['start'])) ? $params['start'] : 0;
			$length = (isset($params['length']) && $params['length'] > 0) ? $params['length'] : 50;
			$offset = ($start - 1) * $length;

			$MainQuery = Game::
			when(isset($params['name']) && strlen($params['name']) > 1, function ($query) use ($params) {
				$query->whereRaw("`name` LIKE '%{$params['name']}%' OR game_id LIKE '%{$params['name']}%'");
			})
				->when(isset($params['soon']) && is_numeric($params['soon']) && $params['soon'] >= 0, function ($query) use ($params) {
					$query->where('soon', (int)$params['soon']);
				})
				->when(isset($params['is_recomended']) && is_numeric($params['is_recomended']) && $params['is_recomended'] >= 0, function ($query) use ($params) {
					$query->where('is_recomended', (int)$params['is_recomended']);
				})
				->when(isset($params['is_fun']) && is_numeric($params['is_fun']) && $params['is_fun'] >= 0, function ($query) use ($params) {
					$query->where('is_fun', (int)$params['is_fun']);
				})
				->when(isset($params['active_on_site']) && is_numeric($params['active_on_site']) && $params['active_on_site'] >= 0, function ($query) use ($params) {
					$query->where('active_on_site', (int)$params['active_on_site']);
				});

			return [
				'total' => $MainQuery->count(),
				'items' => $MainQuery->limit($length)
					->offset($offset)->with([
						'categories',
						'provider',
						'casinos' => function ($q) {
							$q->select(['casinos.int_casino_id', 'casinos.name']);
						},
					])->get(),

			];
		}

		/**
		 * @param array $params
		 * @return Game
		 * @throws ApiResponseException
		 */
		public function save(array $params = [])
		{
			$path = config('casino.uploads.games');
			try {
				$ID = (isset($params['id']) ? (int)$params['id'] : 0);

				if (!$ID) {
					$Game = Game::create([
						'name' => $params['name'],
						'game_id' => $params['game_id'],
						'slug' => Str::slug($params['name']),
						'description' => $params['description'],
						'iframe_url' => $params['iframe_url'],
						'provider_id' => (int)$params['provider_id'],
						'soon' => (int)$params['soon'],
						'active_on_site' => (int)$params['active_on_site'],
						'is_recommended' => (int)$params['is_recommended'],
						'is_fun' => (int)$params['is_fun'],
						'is_fullpage' => (int)$params['is_fullpage'],
					]);

					//append categories
					if (isset($params['categories'])) {
						$categories = json_decode($params['categories']);
						if (is_array($categories) && count($categories) > 0) {
							$Game->categories()->sync($categories);
						}
					}

					//append casinos
					if (isset($params['casinos'])) {
						$casinos = json_decode($params['casinos']);
						if (is_array($casinos) && count($casinos) > 0) {
							$Game->casinos()->sync($casinos);
						}
					}

					//upload thumbnail
					if (isset($params['thumbnail']) && $params['thumbnail'] !== 'null') {
						$filePath = $path . $Game->thumbnail;
						$thumbnail = $this->uploadThumbnail($params['thumbnail'], $path, $params['name'], function () use ($filePath) {
							if (@is_file(public_path($filePath))) {
								@unlink(public_path($filePath));
							}
						});
						$Game->thumbnail = $thumbnail;
						$Game->save();
					}

					return $Game;
				}

				$Game = Game::find($params['id']);
				if (!$Game) {
					throw new \Exception('Invalid ID');
				}
				$Game->update([
					'name' => $params['name'],
					'description' => $params['description'],
					'iframe_url' => $params['iframe_url'],
					'provider_id' => (int)$params['provider_id'],
					'soon' => (int)$params['soon'],
					'active_on_site' => (int)$params['active_on_site'],
					'is_recommended' => (int)$params['is_recommended'],
					'is_fun' => (int)$params['is_fun'],
					'is_fullpage' => (int)$params['is_fullpage'],
					'game_id' => $params['game_id']
				]);

				//append categories
				if (isset($params['categories'])) {
					$categories = json_decode($params['categories']);
					if (is_array($categories) && count($categories) > 0) {
						$Game->categories()->sync($categories);
					}
				}

				//append casinos
				if (isset($params['casinos'])) {
					$casinos = json_decode($params['casinos']);
					if (is_array($casinos) && count($casinos) > 0) {
						$Game->casinos()->sync($casinos);
					}
				}

				//upload thumbnail
				if (isset($params['thumbnail']) && $params['thumbnail'] !== 'null') {
					$filePath = $path . $Game->thumbnail;
					$thumbnail = $this->uploadThumbnail($params['thumbnail'], $path, $params['name'], function () use ($filePath) {
						if (@is_file(public_path($filePath))) {
							@unlink(public_path($filePath));
						}
					});
					$Game->thumbnail = $thumbnail;
					$Game->save();
				}

				return $Game;
			} catch (QueryException $exception) {
				activity()
					->causedBy(null)
					->withProperties([
						'message' => $exception->getMessage(),
						'line' => $exception->getLine(),
						'code' => $exception->getCode(),
						'file' => $exception->getFile()
					])
					->log(config('errors.31'));
				throw  new ApiResponseException($exception->getMessage());
			}
		}

		/**
		 * @param $id
		 * @return string[]
		 */
		public function remove($id)
		{
			return $this->deleteByID(Game::class, $id, function ($model) {
				$filePath = config('casino.uploads.games') . $model->thumbnail;

				if (@is_file(public_path($filePath))) {
					@unlink(public_path($filePath));
				}
			});
		}

		/**
		 * @return array
		 */
		public function getArrayList(): array
		{
			return ['items' => $this->getByParmas(Game::query()->selectRaw('game_id as id, name as value'), [])];
		}

		/**
		 * @param $ID
		 * @return array
		 */
		public function getItem($ID): array
		{
			$Item = $this->getByID(Game::query(), $ID);
			$Item['item']->categories = $Item['item']->categories()->pluck('id')->toArray();
			$Item['item']->casinos = $Item['item']->casinos()
				->pluck('casinos.int_casino_id')
				->toArray();

			return $Item;
		}

		public function lightSearchByName(Request $request)
		{
			return Game::
			when($request->has('name') && strlen($request->get('name')) > 1, function ($query) use ($request) {
				$query->whereRaw("`name` LIKE '%{$request->get('name')}%' OR game_id LIKE '%{$request->get('name')}%'");
			})->selectRaw('name, id, game_id, thumbnail')
				->get()->toArray();
		}

		public function getGamesByCategories(Request $request, $with = [])
		{
			$categories = json_decode($request->get('categories'));
			return Game::whereHas('categories', function ($query) use ($categories) {
				$query->whereIn('categories.id', $categories);
			})->with($with)->get()->toArray();
		}

		public function getByIds(Request $request)
		{
			$GameIds = is_array($request->get('games')) ? (array)$request->get('games') : [$request->get('games')];

			return Game::whereIn('id', $GameIds)->get()->toArray();
		}

		public function getGamesBySection($sectionID): array
		{
			if (!$sectionID) return [];

			$Section = Sections::find($sectionID);
			if (!$Section) return [];

			$Games = $Section->data['games'];
			if (!is_array($Games) || (is_array($Games) && count($Games) == 0)) return [];

			return Game::whereIn('id', $Games)->get()->toArray();
		}
	}
