<?php

	namespace App\Http\Controllers\Api;

	use App\Http\Controllers\Controller;
	use App\Repositories\Stats\Contracts\RiskReportServiceInterface;
	use Illuminate\Http\Request;

	class RiskController extends Controller
	{
		public function overview(Request $request, RiskReportServiceInterface $svc)
		{
			$data = $request->validate([
				'from' => 'required|date',
				'to' => 'required|date',
				'currency_code' => 'nullable|string|max:16', // PEP
				'min_bets' => 'nullable|integer|min:1|max:100000',
				'min_wagered' => 'nullable|string|max:32',
				'int_casino_id' => 'nullable|string',
			]);

			return $svc->overview($data);
		}

		public function players(Request $request, RiskReportServiceInterface $svc)
		{
			$data = $request->validate([
				'from' => 'required|date',
				'to' => 'required|date',
				'currency_code' => 'nullable|string|max:16',
				'min_bets' => 'nullable|integer|min:1|max:100000',
				'min_wagered' => 'nullable|string|max:32',
				'limit' => 'nullable|integer|min:1|max:200',
				'int_casino_id' => 'nullable|string',
			]);

			return $svc->players($data);
		}

		public function duplicates(Request $request, RiskReportServiceInterface $svc)
		{
			$data = $request->validate([
				'from' => 'required|date',
				'to' => 'required|date',
				'currency_code' => 'nullable|string|max:16',
				'limit' => 'nullable|integer|min:1|max:500',
				'int_casino_id' => 'nullable|string',
			]);

			return $svc->duplicates($data);
		}

		public function gameAbuse(Request $request, RiskReportServiceInterface $svc)
		{
			$data = $request->validate([
				'from' => 'required|date',
				'to' => 'required|date',
				'currency_code' => 'nullable|string|max:16',
				'limit' => 'nullable|integer|min:1|max:200',
				'int_casino_id' => 'nullable|string',
			]);

			return $svc->gameAbuse($data);
		}
	}