<?php

	namespace App\Http\Controllers\Api;

	use App\Http\Controllers\Controller;
	use Illuminate\Http\Request;
	use App\Marketing\Contracts\MarketingServiceInterface;

	class MarketingController extends Controller
	{
		public function overview(Request $request, MarketingServiceInterface $svc)
		{
			$filters = $this->validateFilters($request);
			return response()->json(['status' => 'success', 'result' => $svc->overview($filters)]);
		}

		public function cohorts(Request $request, MarketingServiceInterface $svc)
		{
			$filters = $this->validateFilters($request);
			return response()->json(['status' => 'success', 'result' => $svc->cohorts($filters)]);
		}

		public function games(Request $request, MarketingServiceInterface $svc)
		{
			$filters = $this->validateFilters($request);
			return response()->json(['status' => 'success', 'result' => $svc->games($filters)]);
		}

		public function segments(Request $request, MarketingServiceInterface $svc)
		{
			$filters = $this->validateFilters($request) + $request->validate([
					'inactive_days' => ['nullable', 'integer', 'min:1', 'max:365'],
					'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
				]);

			return response()->json(['status' => 'success', 'result' => $svc->segments($filters)]);
		}

		public function funnel(Request $request, MarketingServiceInterface $svc)
		{
			$filters = $this->validateFilters($request);
			return response()->json(['status' => 'success', 'result' => $svc->funnel($filters)]);
		}

		private function validateFilters(Request $request): array
		{
			return $request->validate([
				'int_casino_id' => ['nullable', 'string'],
				'from' => ['required', 'date'],
				'to' => ['required', 'date', 'after:from'],
				'currency_code' => ['nullable', 'string', 'max:20'], // PEP or SOLANA:PEP
			]);
		}
	}