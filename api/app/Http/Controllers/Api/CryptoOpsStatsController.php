<?php

	namespace App\Http\Controllers\Api;

	use App\Http\Controllers\Controller;
	use Illuminate\Http\Request;
	use App\Repositories\Stats\Contracts\CryptoOpsDashboardServiceInterface;
	use DB;

	class CryptoOpsStatsController extends Controller
	{
		/**
		 * Handle the incoming request.
		 */
		public function __invoke(Request $request, CryptoOpsDashboardServiceInterface $svc)
		{
			$data = $request->validate([
				'currency_code' => ['nullable', 'string', 'max:20'], // PEP sau SOLANA:PEP
				'from' => ['required', 'date'],
				'to' => ['required', 'date', 'after:from'],
				'int_casino_id' => ['nullable', 'string']
			]);

			return response()->json([
				'status' => 'success',
				'result' => $svc->sweepsReport($data),
			]);
		}

		/**
		 * @param Request $request
		 * @return \Illuminate\Http\JsonResponse
		 */
		public function listSweeps(Request $request)
		{
			$data = $request->validate([
				'currency_code' => ['nullable', 'string', 'max:20'],
				'status' => ['nullable', 'in:pending,confirmed,failed'],
				'from' => ['required', 'date'],
				'to' => ['required', 'date', 'after:from'],
				'page' => ['nullable', 'integer', 'min:1'],
				'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
				'int_casino_id' => ['nullable', 'string']
			]);

			$currencyDb = str_contains(strtoupper($data['currency_code'] ?? ''), ':')
				? strtoupper($data['currency_code'])
				: (isset($data['currency_code']) ? "SOLANA:" . strtoupper($data['currency_code']) : null);

			$q = DB::table('transaction as t')
				->leftJoin('wallets as w', 'w.id', '=', 't.wallet_id')
				->leftJoin('players as p', function ($join) {
					$join->on('p.id', '=', 'w.holder_id')
						->where('w.holder_type', '=', 'App\\Models\\Player');
				})
				->leftJoin('casinos as c', function ($join) {
					$join->on('c.id', '=', 'w.holder_id')
						->where('w.holder_type', '=', 'App\\Models\\Casino');
				})
				->where('t.type', 'sweep')
				->where('t.created_at', '>=', $data['from'])
				->where('t.created_at', '<', $data['to'])
				->when($currencyDb, fn($qq) => $qq->where('t.currency', $currencyDb))
				->when($data['status'] ?? null, fn($qq) => $qq->where('t.status', $data['status']))
				->when($data['int_casino_id'] ?? null, fn($qq) => $qq->where('t.int_casino_id', $data['int_casino_id']))
				->orderByDesc('t.id')
				->select([
					't.uuid', 't.currency', 't.amount', 't.amount_base', 't.decimals', 't.status', 't.txid',
					't.from_address', 't.to_address', 't.meta', 't.created_at',
					DB::raw('COALESCE(p.username, c.username) as username'),
				]);

			$perPage = (int)($data['per_page'] ?? 50);

			return response()->json([
				'status' => 'success',
				'result' => $q->paginate($perPage),
			]);
		}

	}