<?php

	namespace App\Http\Controllers\Api;

	use App\Http\Controllers\Controller;
	use Illuminate\Http\Request;
	use App\Http\Resources\CryptoTransactionResource;
	use App\Repositories\Stats\Contracts\CryptoTransactionsServiceInterface;

	class CryptoTransactionsController extends Controller
	{
		/**
		 * Handle the incoming request.
		 */
		public function __invoke(Request $request, CryptoTransactionsServiceInterface $svc)
		{
			$data = $request->validate([
				'currency_code' => ['nullable', 'string', 'max:16'], // PEP sau SOLANA:PEP

				// validare exactă (cum ai cerut)
				'type' => ['nullable', 'in:deposit,withdraw,sweep,game_bet,game_win'],
				'status' => ['nullable', 'in:pending,confirmed,failed'],

				// date range
				'from' => ['nullable', 'date'],
				'to' => ['nullable', 'date'],

				// pagination
				'page' => ['nullable', 'integer', 'min:1'],
				'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],

				//casino
				'int_casino_id' => ['nullable', 'string'],
			]);

			// guard: dacă vin ambele, verifică ordinea
			if (!empty($data['from']) && !empty($data['to']) && $data['from'] > $data['to']) {
				return response()->json([
					'message' => 'Invalid range: from must be <= to',
					'errors' => ['from' => ['from must be <= to']],
				], 422);
			}

			$p = $svc->paginate($data);

			return CryptoTransactionResource::collection($p)
				->additional([
					'meta' => [
						'page' => $p->currentPage(),
						'per_page' => $p->perPage(),
						'total' => $p->total(),
						'filters' => $data
					],
				]);
		}
	}