<?php

	namespace App\Http\Controllers\FE;

	use App\Http\Controllers\Controller;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use App\Repositories\Crypto\Contracts\WalletQueryServiceInterface;
	use App\Http\Resources\WalletResource;

	class WalletController extends Controller
	{
		/**
		 * @param Request $request
		 * @param WalletQueryServiceInterface $wallets
		 * @return JsonResponse
		 */
		public function __invoke(Request $request, WalletQueryServiceInterface $wallets): JsonResponse
		{
			$user = $request->user();
			$items = $wallets->getActiveWalletsForHolder($user->getMorphClass(), (int)$user->id);
 
			return response()->json([
				'wallets' => WalletResource::collection($items),
			]);
		}

		/**
		 * @param Request $request
		 * @param WalletQueryServiceInterface $svc
		 * @return JsonResponse
		 */
		public function current(Request $request, WalletQueryServiceInterface $svc): JsonResponse
		{
			$player = $request->user();

			$view = $svc->getCurrentWalletForPlayer($player);

			return response()->json([
				'data' => $view ? (new WalletResource($view))->toArray($request) : null,
			]);
		}

		/**
		 * @param Request $request
		 * @param WalletQueryServiceInterface $svc
		 * @return JsonResponse
		 */
		public function setCurrent(Request $request, WalletQueryServiceInterface $svc): JsonResponse
		{
			$player = $request->user();

			$request->validate([
				'wallet_id' => 'required|integer',
			]);

			$svc->setCurrentWalletForPlayer($player, (int)$request->wallet_id);

			$view = $svc->getCurrentWalletForPlayer($player);

			return response()->json([
				'data' => $view ? (new WalletResource($view))->toArray($request) : null,
			]);
		}
	}