<?php

namespace App\Http\Controllers\FE;

use App\Http\Controllers\Controller;
use App\Repositories\Bundles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BundleController extends Controller
{
	public function active(Request $request, Bundles $bundles): JsonResponse
	{
		return response()->json([
			'success' => true,
			'data' => $bundles->activeMinimal((int)$request->get('limit', 24)),
		]);
	}
}
