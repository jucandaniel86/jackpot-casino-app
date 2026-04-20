<?php

	namespace App\Http\Controllers\FE;

	use App\Http\Controllers\Controller;
	use Illuminate\Http\Request;
	use App\Repositories\Crypto\FX\Contracts\ExchangeRateServiceInterface;

	class ExchangeRateController extends Controller
	{
		public function show(Request $request, ExchangeRateServiceInterface $svc)
		{
			$data = $request->validate([
				'currency' => ['required', 'string', 'max:10'], // PEP
				'fiat' => ['required', 'string', 'size:3'],     // EUR
			]);

			$out = $svc->getRate($data['currency'], $data['fiat']);

			return response()->json([
				'status' => 'success',
				'result' => $out,
			]);
		}
	}