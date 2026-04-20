<?php

	namespace App\Http\Controllers\Api;

	use App\Exceptions\ApiResponseException;
	use App\Http\Controllers\Controller;
	use App\Http\Requests\CategoryRequest;
	use App\Http\Responses\ApiResponseClass;
	use App\Interfaces\CategoriesInterface;
	use App\Interfaces\MenuInterface;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;

	class MenuController extends Controller
	{
		protected $service;

		public function __construct(MenuInterface $categories)
		{
			$this->service = $categories;
		}

		/**
		 * @url /menu/save
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function save(Request $request): JsonResponse
		{
			$request->validate(['title' => 'required']);

			DB::beginTransaction();

			try {
				$MenuItem = $this->service->save($request);

				DB::commit();
				return ApiResponseClass::sendResponse(
					$MenuItem,
					'The menu item was saved successfully',
					201
				);

			} catch (\Exception $ex) {
				ApiResponseClass::rollback($ex);
				return ApiResponseClass::sendResponse(['error' => $ex->getMessage()], 'Error');
			}
		}

		/**
		 * @url /menu/list
		 * @method GET
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function list(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->items($request), '');
		}

		/**
		 * @url /menu/delete
		 * method DELETE
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function remove(Request $request): JsonResponse
		{
			try {
				return ApiResponseClass::sendResponse($this->service->remove($request->id), 'The menu item was deleted successfully');
			} catch (ApiResponseException $exception) {
				return ApiResponseClass::sendError(['error' => $exception->getMessage()], 'Error');
			}
		}
	}