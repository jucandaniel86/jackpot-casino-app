<?php

	namespace App\Http\Controllers\Api;

	use App\Exceptions\ApiResponseException;
	use App\Http\Controllers\Controller;
	use App\Http\Responses\ApiResponseClass;
	use App\Interfaces\SliderInterface;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;

	class SliderController extends Controller
	{
		protected $service;

		public function __construct(SliderInterface $games)
		{
			$this->service = $games;
		}

		/**
		 * @url /sliders/save
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function save(Request $request): JsonResponse
		{
			$request->validate(['name' => 'required']);

			DB::beginTransaction();
			try {
				$Slider = $this->service->save($request->all());

				DB::commit();
				return ApiResponseClass::sendResponse(
					$Slider,
					'The slider was saved successfully',
					201
				);

			} catch (\Exception $ex) {
				ApiResponseClass::rollback($ex);
				return ApiResponseClass::sendResponse(['error' => $ex->getMessage()], 'Error');
			}
		}

		/**
		 * @url /sliders/list
		 * @method GET
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function list(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->list($request->all()), '');
		}

		/**
		 * @url /sliders/delete
		 * method DELETE
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function remove(Request $request): JsonResponse
		{
			try {
				return ApiResponseClass::sendResponse($this->service->remove($request->id), 'The slider was deleted successfully');
			} catch (ApiResponseException $exception) {
				return ApiResponseClass::sendError(['error' => $exception->getMessage()], 'Error');
			}
		}
	}