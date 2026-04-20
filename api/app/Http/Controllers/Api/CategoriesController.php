<?php

	namespace App\Http\Controllers\Api;

	use App\Exceptions\ApiResponseException;
	use App\Http\Controllers\Controller;
	use App\Http\Requests\CategoryRequest;
	use App\Http\Responses\ApiResponseClass;
	use App\Interfaces\CategoriesInterface;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;

	class CategoriesController extends Controller
	{
		protected $service;

		public function __construct(CategoriesInterface $categories)
		{
			$this->service = $categories;
		}

		/**
		 * @url /categories/save
		 * @param CategoryRequest $request
		 * @return JsonResponse
		 */
		public function save(CategoryRequest $request): JsonResponse
		{
			DB::beginTransaction();
			$details = $request->only(['name', 'parent_id', 'seo', 'restricted', 'icon', 'id']);

			try {
				$Category = $this->service->save($details);

				DB::commit();
				return ApiResponseClass::sendResponse(
					$Category,
					'The category was saved successfully',
					201
				);

			} catch (\Exception $ex) {
				ApiResponseClass::rollback($ex);
				return ApiResponseClass::sendResponse(['error' => $ex->getMessage()], 'Error');
			}
		}

		/**
		 * @url /categories/list
		 * @method GET
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function list(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->list($request->all()), '');
		}

		/**
		 * @url /categories/delete
		 * method DELETE
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function remove(Request $request): JsonResponse
		{
			try {
				return ApiResponseClass::sendResponse($this->service->remove($request->id), 'The category was deleted successfully');
			} catch (ApiResponseException $exception) {
				return ApiResponseClass::sendError(['error' => $exception->getMessage()], 'Error');
			}
		}
	}