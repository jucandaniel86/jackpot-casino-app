<?php

	namespace App\Http\Controllers\FE;

	use App\Http\Controllers\Controller;
	use App\Interfaces\GameSessionsInterface;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;

	class GameSessionController extends Controller
	{
		private $service;

		public function __construct(GameSessionsInterface $service)
		{
			$this->service = $service;
		}

		/**
		 * @url /players/play
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function start(Request $request): JsonResponse
		{
			return response()->json($this->service->start($request));
		}

		/**
		 * @url /demo
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function demo(Request $request): JsonResponse
		{
			return response()->json($this->service->startDemo($request));
		}
	}