<?php

	namespace App\Http\Controllers\Api;

	use App\Http\Controllers\Controller;
	use App\Interfaces\SectionInterface;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;
	use App\Http\Responses\ApiResponseClass;
	use App\Exceptions\ApiResponseException;

	class SectionController extends Controller
	{
		protected $service;

		public function __construct(SectionInterface $service)
		{
			$this->service = $service;
		}

		/**
		 * @url /sections/save-draft
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function saveDraftSection(Request $request): JsonResponse
		{
			DB::beginTransaction();

			try {
				$Section = $this->service->addNewDraft($request);

				DB::commit();
				return ApiResponseClass::sendResponse(
					$Section,
					'The section was saved successfully as DRAFT',
					201
				);

			} catch (\Exception $ex) {
				ApiResponseClass::rollback($ex);
				return ApiResponseClass::sendResponse(['error' => $ex->getMessage()], 'Error');
			}
		}

		/**
		 * @url /sections/save-data
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function saveSectionData(Request $request): JsonResponse
		{
			DB::beginTransaction();

			try {
				$Section = $this->service->saveSectionData($request);

				DB::commit();
				return ApiResponseClass::sendResponse(
					$Section,
					'The section was saved successfully',
					201
				);

			} catch (\Exception $ex) {
				ApiResponseClass::rollback($ex);
				return ApiResponseClass::sendResponse(['error' => $ex->getMessage()], 'Error');
			}
		}

		/**
		 * @url /sections/delete
		 * method DELETE
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function remove(Request $request): JsonResponse
		{
			try {
				return ApiResponseClass::sendResponse($this->service->remove($request->id), 'The section was deleted successfully');
			} catch (ApiResponseException $exception) {
				return ApiResponseClass::sendError(['error' => $exception->getMessage()], 'Error');
			}
		}

		/**
		 * @url /sections/change-order
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function changeSectionOrder(Request $request): JsonResponse
		{
			DB::beginTransaction();

			try {
				$Section = $this->service->changeSectionOrder($request->all());

				DB::commit();
				return ApiResponseClass::sendResponse(
					$Section,
					'The order was saved successfully',
					201
				);

			} catch (\Exception $ex) {
				ApiResponseClass::rollback($ex);
				return ApiResponseClass::sendResponse(['error' => $ex->getMessage()], 'Error');
			}
		}

		public function homeBoxes(): JsonResponse
		{
			return ApiResponseClass::sendResponse(config('casino.homebox'), '');
		}
	}