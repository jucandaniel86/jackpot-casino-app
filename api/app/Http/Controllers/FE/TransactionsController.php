<?php

	namespace App\Http\Controllers\FE;

	use App\Http\Controllers\Controller;
	use Illuminate\Http\Request;
	use App\Repositories\Crypto\Contracts\TransactionQueryServiceInterface;
	use App\Http\Resources\TransactionResource;

	class TransactionsController extends Controller
	{
		/**
		 * Handle the incoming request.
		 */
		public function __invoke(Request $request, TransactionQueryServiceInterface $svc)
		{
			$user = $request->user();

			$validated = $request->validate([
				'currency' => 'nullable|string|max:32',
				'type' => 'nullable|in:deposit,withdraw',
				'status' => 'nullable|string|max:32',
				'from' => 'nullable|date',
				'to' => 'nullable|date',
				'per_page' => 'nullable|integer|min:1|max:200',
			]);

			$paginator = $svc->paginate(array_merge($validated, [
				'holder_type' => $user->getMorphClass(),
				'holder_id' => (int)$user->id,
			]));

			return TransactionResource::collection($paginator);
		}
	}