<?php

	namespace App\Http\Controllers\Api;

	use App\Exceptions\ApiResponseException;
	use App\Http\Controllers\Controller;
	use App\Http\Requests\CategoryRequest;
	use App\Http\Responses\ApiResponseClass;
	use App\Interfaces\ProvidersInterface;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;

	class ProvidersController extends Controller
	{
		protected $service;

		public function __construct(ProvidersInterface $providers)
		{
			$this->service = $providers;
		}

		/**
		 * @url /providers/save
		 * @param CategoryRequest $request
		 * @return JsonResponse
		 */
		public function save(CategoryRequest $request): JsonResponse
		{
			DB::beginTransaction();
			$details = $request->only(['name', 'slug', 'thumbnail_file', 'active', 'id']);

			try {
				$Provider = $this->service->save($details);

				DB::commit();
				return ApiResponseClass::sendResponse(
					$Provider,
					'The provider was saved successfully',
					201
				);

			} catch (\Exception $ex) {
				ApiResponseClass::rollback($ex);
				return ApiResponseClass::sendResponse(['error' => $ex->getMessage()], 'Error');
			}
		}

		/**
		 * @url /providers/list
		 * @method GET
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function list(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->list($request->all()), '');
		}

		/**
		 * @url /providers/delete
		 * method DELETE
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function remove(Request $request): JsonResponse
		{
			try {
				return ApiResponseClass::sendResponse($this->service->remove($request->id), 'The provider was deleted successfully');
			} catch (ApiResponseException $exception) {
				return ApiResponseClass::sendError(['error' => $exception->getMessage()], 'Error');
			}
		}
	}