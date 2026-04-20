<?php

	namespace App\Http\Controllers\Api;

	use App\Exceptions\ApiResponseException;
	use App\Http\Controllers\Controller;
	use App\Http\Responses\ApiResponseClass;
	use App\Interfaces\WalletInterface;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;

	class WalletsController extends Controller
	{
		protected $service;

		public function __construct(WalletInterface $service)
		{
			$this->service = $service;
		}

		/**
		 * @url /wallet/save
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function save(Request $request): JsonResponse
		{
			$request->validate([
				'name' => 'required',
				'code' => 'required'
			]);

			DB::beginTransaction();

			try {
				$Wallet = $this->service->save($request->all());

				DB::commit();
				return ApiResponseClass::sendResponse(
					$Wallet,
					'The wallet was saved successfully',
					201
				);

			} catch (\Exception $ex) {
				ApiResponseClass::rollback($ex);
				return ApiResponseClass::sendResponse(['error' => $ex->getMessage()], 'Error');
			}
		}

		/**
		 * @url /wallet/list
		 * @method GET
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function list(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->list($request->all()), '');
		}

		/**
		 * @url /wallet/delete
		 * method DELETE
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function remove(Request $request): JsonResponse
		{
			try {
				return ApiResponseClass::sendResponse($this->service->remove($request->id), 'The wallet was deleted successfully');
			} catch (ApiResponseException $exception) {
				return ApiResponseClass::sendError(['error' => $exception->getMessage()], 'Error');
			}
		}

		public function createUserWallets(): JsonResponse
		{
			try {
				return ApiResponseClass::sendResponse($this->service->createUserWallets(), 'Success');
			} catch (ApiResponseException $exception) {
				return ApiResponseClass::sendError(['error' => $exception->getMessage()], 'Error');
			}
		}

		/**
		 * @url /wallet/currencies
		 * @method GET
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function currencies(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->currencies($request->all()), '');
		}
	}