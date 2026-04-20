<?php

	namespace App\Http\Controllers\Api;

	use App\Exceptions\ApiResponseException;
	use App\Http\Controllers\Controller;
	use App\Http\Requests\GameRequest;
	use App\Http\Responses\ApiResponseClass;
	use App\Interfaces\GameInterface;
	use App\Models\Game;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Str;

	class GameController extends Controller
	{
		protected $service;

		public function __construct(GameInterface $games)
		{
			$this->service = $games;
		}

		/**
		 * @url /games/save
		 * @param GameRequest $request
		 * @return JsonResponse
		 */
		public function save(GameRequest $request): JsonResponse
		{
			DB::beginTransaction();
			try {
				$Game = $this->service->save($request->all());

				DB::commit();
				return ApiResponseClass::sendResponse(
					$Game,
					'The game was saved successfully',
					201
				);

			} catch (\Exception $ex) {
				ApiResponseClass::rollback($ex);
				return ApiResponseClass::sendResponse(['error' => $ex->getMessage()], 'Error');
			}
		}

		/**
		 * @url /games/list
		 * @method GET
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function list(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->list($request->all()), '');
		}

		/**
		 * @url /games/delete
		 * method DELETE
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function remove(Request $request): JsonResponse
		{
			try {
				return ApiResponseClass::sendResponse($this->service->remove($request->id), 'The game was deleted successfully');
			} catch (ApiResponseException $exception) {
				return ApiResponseClass::sendError(['error' => $exception->getMessage()], 'Error');
			}
		}

		/**
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function get(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->getItem($request->get('id')), '');
		}

		/**
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function getArray(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->getArrayList($request->get('id')), '');
		}

		/**
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function lightSearch(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->lightSearchByName($request), '');
		}

		/**
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function getGamesByCategories(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->getGamesByCategories($request), '');
		}

		/**
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function getByIds(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->getByIds($request), '');
		}


		/**
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function getGamesBySection(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->getGamesBySection($request->get('section')), '');
		}

		public function importGames()
		{
			$filePath = '/imports/featured.json';
			$JsonFileToImport = file_get_contents(public_path($filePath));
			$Data = json_decode($JsonFileToImport);
			$CategoryID = 121;
			$ImagePath = public_path('/import-images/');
			$upload = true;

			foreach ($Data as $game) {
				$Check = Game::query()->where('name', $game->name)
					->with('categories')->first();
				if ($Check) {
					$CheckAsociation = DB::table('games_categories')->where('game_id', $Check->id)
						->where('category_id', $CategoryID)->first();
					if (!$CheckAsociation) {
						$Check->categories()->attach($CategoryID);
					}


					continue;
				}
				$newFilename = '';

				if ($upload) {
					$fileNameExp = explode('/', $game->imageUrl);
					$extThumbnail = end($fileNameExp);

					$externalFilename = $ImagePath . $extThumbnail;

					if (file_exists($externalFilename)) {
						$ext = pathinfo($externalFilename, PATHINFO_EXTENSION);

						$newFilename = Str::slug($game->name) . '_' . Str::random(5) . '.' . $ext;

						copy($externalFilename, public_path('/uploads/games/' . $newFilename));
					}
				}


				$Game = new Game();
				$Game->name = $game->name;
				$Game->slug = Str::slug($game->name);
				$Game->game_id = rand(0, 1000);
				$Game->internal_id = Str::uuid();
				$Game->iframe_url = $game->name;
				$Game->is_fun = $game->hasDemo ? 1 : 0;
				$Game->active_on_site = 1;
				$Game->description = $game->name;
				$Game->provider_id = 21;
				$Game->thumbnail = $newFilename;
				$Game->save();

				$Game->categories()->sync([$CategoryID]);
			}
		}

	}