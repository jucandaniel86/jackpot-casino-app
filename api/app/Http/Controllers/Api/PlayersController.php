<?php

	namespace App\Http\Controllers\Api;

	use App\Http\Controllers\Controller;
	use App\Http\Responses\ApiResponseClass;
	use App\Interfaces\PlayersInterface;
	use App\Repositories\PlayerActivity;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;

	class PlayersController extends Controller
	{
		private $service;

		public function __construct(PlayersInterface $service)
		{
			$this->service = $service;
		}

		/**
		 * @url /api/players/list
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function getList(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->list($request->all()), '');
		}

		public function overview(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->overview($request->all()), '');
		}

		/**
		 * @url /api/players/activity
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function userActivityList(Request $request, PlayerActivity $activity): JsonResponse
		{
			return ApiResponseClass::sendResponse($activity->userList($request->all()), '');
		}

		/**
		 * @url /api/players/wallets
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function userWallets(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->userWallets($request->get('id')), '');
		}

		/**
		 * @url /api/players/sessions
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function userSessions(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->service->playerSessions($request->get('id')), '');
		}
	}
