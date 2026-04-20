<?php

	namespace App\Http\Controllers\Api;

	use App\Http\Controllers\Controller;
	use App\Http\Responses\ApiResponseClass;
	use App\Interfaces\BetInterface;
	use App\Models\Game;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;

	class BetController extends Controller
	{
		private $service;

		public function __construct(BetInterface $service)
		{
			$this->service = $service;
		}

		/**
		 * @method GET
		 * @url /bets/search
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function search(Request $request): JsonResponse
		{
			$request->validate([
				'int_casino_id' => 'nullable|string',
			]);

			return ApiResponseClass::sendResponse($this->service->search($request), '');
		}
 
	}
