<?php

	namespace App\Http\Controllers\Api;

	use App\Repositories\Crypto\Contracts\TreasuryDistributionServiceInterface;
	use App\Http\Controllers\Controller;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;

	class TreasuryDistributionController extends Controller
	{
		/**
		 * Handle the incoming request.
		 */
		public function __invoke(Request $request, TreasuryDistributionServiceInterface $svc): JsonResponse
		{
			$data = $request->validate([
				'currency' => 'required|string|max:32', // ex SOLANA:PEP
				'int_casino_id' => 'nullable|string',
			]);
			try {
				return response()->json([
					'success' => true,
					'currency_id' => $data['currency'],
					'series' => $svc->getDistribution($data['currency'], intCasinoId: $data['int_casino_id'] ?? null),
				]);
			} catch (\Exception $exception) {
				return response()->json([
					'success' => false,
					'message' => $exception->getMessage()
				]);
			}

		}
	}
