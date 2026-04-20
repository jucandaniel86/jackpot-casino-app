<?php

	namespace App\Http\Controllers\Api;

	use App\Http\Controllers\Controller;
	use Illuminate\Http\Request;
	use App\Repositories\Crypto\Withdraw\Contracts\WithdrawRequestServiceInterface;

	class AdminWithdrawRequestController extends Controller
	{
		public function index(Request $request, WithdrawRequestServiceInterface $svc)
		{
			$data = $request->validate([
				'currency' => ['nullable', 'string', 'max:16'],
				'status' => ['nullable', 'in:pending,approved,completed,rejected,failed'],
				'from' => ['nullable', 'date'],
				'to' => ['nullable', 'date'],
				'page' => ['nullable', 'integer', 'min:1'],
				'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
				'int_casino_id' => ['nullable', 'string']
			]);

			$p = $svc->paginate($data);

			return response()->json([
				'success' => true,
				'data' => $p,
			]);
		}

		public function approve(Request $request, string $uuid, WithdrawRequestServiceInterface $svc)
		{
			$data = $request->validate([
				'note' => ['nullable', 'string', 'max:255'],
			]);

			$svc->approve($uuid, (int)auth()->id(), $data['note'] ?? null);

			return response()->json(['status' => 'success']);
		}

		public function reject(Request $request, string $uuid, WithdrawRequestServiceInterface $svc)
		{
			$data = $request->validate([
				'reason' => ['required', 'string', 'max:255'],
			]);

			$svc->reject($uuid, (int)auth()->id(), $data['reason']);

			return response()->json(['status' => 'success']);
		}

		public function complete(Request $request, string $uuid, WithdrawRequestServiceInterface $svc)
		{
			$data = $request->validate([
				'txid' => ['nullable', 'string', 'max:128'],
				'note' => ['nullable', 'string', 'max:255'],
			]);

			$svc->complete($uuid, (int)auth()->id(), $data['txid'] ?? null, $data['note'] ?? null);

			return response()->json(['status' => 'success']);
		}
	}
