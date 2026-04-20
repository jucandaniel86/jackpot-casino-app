<?php

	namespace App\Http\Controllers\Api;

	use App\Exceptions\ApiResponseException;
	use App\Http\Controllers\Controller;
	use App\Http\Requests\GameRequest;
	use App\Http\Responses\ApiResponseClass;
	use App\Interfaces\GameInterface;
	use App\Interfaces\PromotionInterface;
	use App\Models\Game;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Str;

	class PromotionController extends Controller
	{
		protected $service;

		public function __construct(PromotionInterface $games)
		{
			$this->service = $games;
		}

		/**
		 * @url /promotions/save
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function save(Request $request): JsonResponse
		{
			DB::beginTransaction();
			try {
				$Game = $this->service->save($request->all());

				DB::commit();
				return ApiResponseClass::sendResponse(
					$Game,
					'The promotion was saved successfully',
					201
				);

			} catch (\Exception $ex) {
				ApiResponseClass::rollback($ex);
				return ApiResponseClass::sendResponse(['error' => $ex->getMessage()], 'Error');
			}
		}

		/**
		 * @url /promotions/save-draft
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function saveDraft(Request $request): JsonResponse
		{
			DB::beginTransaction();
			try {
				$Game = $this->service->savePromotionDraft($request->all());

				DB::commit();
				return ApiResponseClass::sendResponse(
					$Game,
					'The promotion was saved successfully',
					201
				);

			} catch (\Exception $ex) {
				ApiResponseClass::rollback($ex);
				return ApiResponseClass::sendResponse(['error' => $ex->getMessage()], 'Error');
			}
		}

		/**
		 * @url /promotions/list
		 * @method GET
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function list(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->list($request->all()), '');
		}

		/**
		 * @url /promotions/delete
		 * method DELETE
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function remove(Request $request): JsonResponse
		{
			try {
				return ApiResponseClass::sendResponse($this->service->remove($request->id), 'The promotion was deleted successfully');
			} catch (ApiResponseException $exception) {
				return ApiResponseClass::sendError(['error' => $exception->getMessage()], 'Error');
			}
		}

		/**
		 * @url /promotions/get/{id}
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function get(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->getItem($request->get('id')), '');
		}
	}