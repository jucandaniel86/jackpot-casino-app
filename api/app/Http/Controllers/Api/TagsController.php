<?php

	namespace App\Http\Controllers\Api;

	use App\Exceptions\ApiResponseException;
	use App\Http\Controllers\Controller;
	use App\Http\Requests\CategoryRequest;
	use App\Http\Responses\ApiResponseClass;
	use App\Interfaces\TagsInterface;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;

	class TagsController extends Controller
	{
		protected $service;

		public function __construct(TagsInterface $service)
		{
			$this->service = $service;
		}

		/**
		 * @url /tags/save
		 * @param CategoryRequest $request
		 * @return JsonResponse
		 */
		public function save(CategoryRequest $request): JsonResponse
		{
			DB::beginTransaction();
			try {
				$TAG = $this->service->save($request->all());

				DB::commit();
				return ApiResponseClass::sendResponse(
					$TAG,
					'The tag was saved successfully',
					201
				);

			} catch (\Exception $ex) {
				ApiResponseClass::rollback($ex);
				return ApiResponseClass::sendResponse(['error' => $ex->getMessage()], 'Error');
			}
		}

		/**
		 * @url /pages/list
		 * @method GET
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function list(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->list($request->all()), '');
		}

		/**
		 * @url /tags/get
		 * @method GET
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function get(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->getTag($request->get('id')), '');
		}

		/**
		 * @url /tags/delete
		 * method DELETE
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function remove(Request $request): JsonResponse
		{
			try {
				return ApiResponseClass::sendResponse($this->service->remove($request->id), 'The page was deleted successfully');
			} catch (ApiResponseException $exception) {
				return ApiResponseClass::sendError(['error' => $exception->getMessage()], 'Error');
			}
		}
	}