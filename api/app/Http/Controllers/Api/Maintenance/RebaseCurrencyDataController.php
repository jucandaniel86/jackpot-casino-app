<?php

namespace App\Http\Controllers\Api\Maintenance;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class RebaseCurrencyDataController extends Controller
{
	public function __invoke(Request $request): JsonResponse
	{
		$data = $request->validate([
			'currency' => ['required', 'string', 'max:32'],
			'from_decimals' => ['required', 'integer', 'min:0', 'max:30'],
			'to_decimals' => ['required', 'integer', 'min:0', 'max:30'],
			'legacy_max_base' => ['nullable', 'string', 'max:65'],
			'apply' => ['nullable', 'boolean'],
			'force_all' => ['nullable', 'boolean'],
		]);

		$params = [
			'--currency' => (string)$data['currency'],
			'--from-decimals' => (int)$data['from_decimals'],
			'--to-decimals' => (int)$data['to_decimals'],
		];

		if (isset($data['legacy_max_base']) && (string)$data['legacy_max_base'] !== '') {
			$params['--legacy-max-base'] = (string)$data['legacy_max_base'];
		}
		if ((bool)($data['apply'] ?? false)) {
			$params['--apply'] = true;
		}
		if ((bool)($data['force_all'] ?? false)) {
			$params['--force-all'] = true;
		}

		$exitCode = Artisan::call('crypto:rebase-currency-data', $params);

		return response()->json([
			'success' => $exitCode === 0,
			'exit_code' => $exitCode,
			'command' => 'crypto:rebase-currency-data',
			'params' => $params,
			'output' => Artisan::output(),
		], $exitCode === 0 ? 200 : 422);
	}
}
