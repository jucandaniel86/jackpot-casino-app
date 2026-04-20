<?php

	namespace App\Http\Controllers\Api;

	use App\Http\Responses\ApiResponseClass;
	use App\Exceptions\ApiResponseException;
	use App\Http\Controllers\Controller;
	use App\Interfaces\UserInterface;
	use App\Models\User;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use App\Http\Requests\{UpdateUserPasswordRequest, UserRequest, UserUpdateRequest};

	use DB;

	class UsersController extends Controller
	{
		private UserInterface $service;

		public function __construct(UserInterface $service)
		{
			$this->service = $service;
		}

		/**
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function getUsersList(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->getUsersList($request->all()), '');
		}

		/**
		 * @param UserRequest $request
		 * @return JsonResponse
		 */
		public function createUser(UserRequest $request): JsonResponse
		{
			DB::beginTransaction();
			$details = $request->only(['name', 'email', 'password', 'roles', 'user_type', 'site_id']);

			try {
				$user = $this->service->createUser($details);

				DB::commit();
				return ApiResponseClass::sendResponse(
					$user,
					'The user was created successfuly',
					201
				);

			} catch (\Exception $ex) {
				ApiResponseClass::rollback($ex);
				return ApiResponseClass::sendResponse(['error' => $ex->getMessage()], 'Error');
			}
		}

		public function updateUser(UserUpdateRequest $request)
		{
			DB::beginTransaction();
			$details = $request->all();

			try {
				$user = $this->service->createUser($details);

				DB::commit();
				return ApiResponseClass::sendResponse(
					$user,
					'The user was saved successfuly',
					201
				);

			} catch (ApiResponseException $ex) {
				ApiResponseClass::rollback($ex);
				return ApiResponseClass::sendError(['errors' => [$ex->getMessage()]], 'Error');
			}
		}

		/**
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function deleteUser(Request $request): JsonResponse
		{
			try {
				return ApiResponseClass::sendResponse($this->service->deleteUser($request->id), 'The user was deleted successfuly');
			} catch (ApiResponseException $exception) {
				return ApiResponseClass::sendError(['error' => $exception->getMessage()], 'Error');
			}
		}

		/**
		 * @param UpdateUserPasswordRequest $request
		 * @return JsonResponse
		 */
		public function changePassword(UpdateUserPasswordRequest $request): JsonResponse
		{
			try {
				return ApiResponseClass::sendResponse($this->service->changePassword($request->all()), 'The user was saved successfuly');
			} catch (ApiResponseException $exception) {
				return ApiResponseClass::sendError(['failed' => [$exception->getMessage()]], 'Error');
			}
		}

		/**
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function changeUserEnvironment(Request $request): JsonResponse
		{
			$request->validate([
				'env' => 'required|in:DEV,UAT,PROD'
			]);
			$request->user()->update([
				'env' => $request->get('env')
			]);
			return ApiResponseClass::sendResponse([
				'user' => $request->user(),
				'env' => $request->get('env')
			], 'The environemnt was changed successfuly');
		}

		/**
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function updateIntCasinoId(Request $request): JsonResponse
		{
			try {
				$data = $request->validate([
					'int_casino_id' => 'nullable|string|exists:casinos,int_casino_id',
				]);

				$user = $request->user();
				if (!$user) {
					return response()->json([
						'success' => false,
						'message' => 'User not authenticated.',
					], 401);
				}

				$user->update([
					'int_casino_id' => $data['int_casino_id'] ?? null,
				]);

				return response()->json([
					'success' => true,
					'message' => 'int_casino_id updated successfully.',
				]);
			} catch (\Throwable $e) {
				return response()->json([
					'success' => false,
					'message' => $e->getMessage(),
				], 500);
			}
		}
	}
