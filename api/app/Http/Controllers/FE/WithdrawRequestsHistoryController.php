<?php

namespace App\Http\Controllers\FE;

use App\Repositories\Crypto\Withdraw\Contracts\WithdrawRequestServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WithdrawRequestsHistoryController extends Controller
{
	public function __invoke(Request $request, WithdrawRequestServiceInterface $svc)
	{
		$playerId = (int)$request->user()->id;
		$items = $svc->listForPlayerLast24h($playerId);

		return response()->json([
			'data' => $items,
		]);
	}
}
