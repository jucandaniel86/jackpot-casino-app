<?php

	namespace App\Http\Controllers\FE;

	use App\Http\Controllers\Controller;
	use App\Models\Wallet;
	use Illuminate\Http\Request;
	use App\Repositories\Crypto\Withdraw\Contracts\WithdrawRequestServiceInterface;

	class WithdrawRequestsController extends Controller
	{
		/**
		 * Handle the incoming request.
		 */
		public function __invoke(Request $request, WithdrawRequestServiceInterface $svc)
		{
			$data = $request->validate([
				'wallet_id' => ['required', 'string'],
				'to_address' => ['required', 'string', 'min:20', 'max:128'], // base58 check optional
				'amount' => ['required', 'string'], // ui string
				'meta' => ['nullable', 'array'],
			]);

			$playerId = (int)$request->user()->id;
			$wallet = Wallet::query()->where('uuid', '=', $data['wallet_id'])->first();

			if(!$wallet) {
				return response()->json(['error' => "Invalid Wallet"], 422);
			}

			$uuid = $svc->createRequest(
				playerId: $playerId,
				walletId: (int)$wallet->id,
				toAddress: $data['to_address'],
				amountUi: $data['amount'],
				meta: $data['meta'] ?? []
			);

			return response()->json(['uuid' => $uuid], 201);
		}
	}