<?php

	namespace App\Http\Controllers\Integrations;

	use App\Http\Controllers\Controller;
	use App\Repositories\Integrations\GameForge;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Log;

	class BetController extends Controller
	{
		/**
		 * Handle the incoming request.
		 */
		public function casinoBetWin(Request $request, GameForge $gameForge): JsonResponse
		{
			$registry = app(\App\Repositories\Integrations\Support\IntegrationsRegistry::class);
			$provider = $registry->tryIntegration('gameforge');

			if (!$provider) {
				Log::channel(config('integrations.game_forge.log_channel'))
					->warning('No provider implemented,', [
						'provider' => 'gameforge',
					]);
				return response()->json([], 400);
			}

			return response()->json($provider->bet($request));
		}

		/**
		 *  $registry = app(\App\Repositories\Integrations\Services\IntegrationApiService::class);
		 * return response()->json($registry->balance($request));
		 */
		public function __invoke(Request $request): JsonResponse
		{
			$registry = app(\App\Repositories\Integrations\Services\IntegrationApiService::class);
			return response()->json($registry->betwin($request));
		}
	}