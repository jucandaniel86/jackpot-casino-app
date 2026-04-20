<?php

	namespace App\Http\Controllers\Api;

	use App\Http\Controllers\Controller;
	use App\Repositories\Stats\Contracts\CryptoOpsStatsServiceInterface;
	use Illuminate\Http\Request;

	class CryptoOpsController extends Controller
	{
		public function index(Request $request, CryptoOpsStatsServiceInterface $svc)
		{
			$data = $request->validate([
				'from' => 'required|date',
				'to' => 'required|date',
				'currency_code' => 'nullable|string|max:16',
				'int_casino_id' => 'nullable|string',
			]);

			return $svc->report($data);
		}
	}
