<?php

	namespace App\Http\Controllers\Api;

	use App\Exceptions\ApiResponseException;
	use App\Http\Controllers\Controller;
	use App\Http\Responses\ApiResponseClass;
	use App\Repositories\IconsRepository;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;

	class IconsController extends Controller
	{
		protected $service;

		public function __construct(IconsRepository $providers)
		{
			$this->service = $providers;
		}

		/**
		 * @url /icons/save
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function save(Request $request): JsonResponse
		{
			DB::beginTransaction();

			try {
				$Provider = $this->service->save($request);

				DB::commit();
				return ApiResponseClass::sendResponse(
					$Provider,
					'The icon was saved successfully',
					201
				);

			} catch (\Exception $ex) {
				ApiResponseClass::rollback($ex);
				return ApiResponseClass::sendResponse(['error' => $ex->getMessage()], 'Error');
			}
		}

		/**
		 * @url /icons/list
		 * @method GET
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function list(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->list(), '');
		}

		/**
		 * @url /icons/delete
		 * method DELETE
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function remove(Request $request): JsonResponse
		{
			try {
				return ApiResponseClass::sendResponse($this->service->remove($request->id), 'The icon was deleted successfully');
			} catch (ApiResponseException $exception) {
				return ApiResponseClass::sendError(['error' => $exception->getMessage()], 'Error');
			}
		}

		/**
		 * @url /icons/import
		 * @method GET
		 * @return JsonResponse
		 */
		public function import(): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->import(), 'The icons was imported successfully');
		}
	}