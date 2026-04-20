<?php

	namespace App\Http\Controllers\Api;

	use App\Http\Controllers\Controller;
	use App\Http\Requests\OperatorRequest;
	use App\Http\Responses\ApiResponseClass;
	use App\Interfaces\OperatorInterface;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;

	class OperatorController extends Controller
	{
		private OperatorInterface $operator;

		public function __construct(OperatorInterface $operator)
		{
			$this->operator = $operator;
		}

		/**
		 * @url /operator/store
		 * @method POST
		 * @param OperatorRequest $request
		 * @return JsonResponse
		 */
		public function store(OperatorRequest $request): JsonResponse
		{
			DB::beginTransaction();
			$details = $request->only(['name', 'site_id', 'id']);

			try {
				$user = $this->operator->store($details);

				DB::commit();
				return ApiResponseClass::sendResponse(
					$user,
					'The operator was created successfuly',
					201
				);

			} catch (\Exception $ex) {
				ApiResponseClass::rollback($ex);
				return ApiResponseClass::sendResponse(['error' => $ex->getMessage()], 'Error');
			}
		}

		/**
		 * @url /operator/list
		 * @method GET
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function list(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->operator->getList($request->all()), '');
		}

		/**
		 * @url /operator/all
		 * @method GET
		 * @return JsonResponse
		 */
		public function operators(): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->operator->allOperators(), '');
		}

	}