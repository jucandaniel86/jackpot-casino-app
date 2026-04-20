<?php

	namespace App\Http\Controllers\Api;

	use App\Http\Controllers\Controller;
	use App\Repositories\Stats\Contracts\FinanceOpsDashboardServiceInterface;
	use Illuminate\Http\Request;

	class FinanceOpsController extends Controller
	{
		/**
		 * @param Request $request
		 * @param FinanceOpsDashboardServiceInterface $svc
		 * @return mixed
		 */
		public function index(Request $request, FinanceOpsDashboardServiceInterface $svc)
		{
			$data = $request->validate([
				'from' => 'required|date',
				'to' => 'required|date',
				'currency_code' => 'nullable|string|max:16', // PEP
				'int_casino_id' => 'nullable|string',
				'unclaimed_days' => 'nullable|integer|min:1|max:365',
			]);

			return $svc->report($data);
		}
	}
