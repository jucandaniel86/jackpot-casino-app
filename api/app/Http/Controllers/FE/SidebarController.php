<?php

namespace App\Http\Controllers\FE;

use App\Http\Controllers\Controller;
use App\Repositories\MenuRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarController extends Controller
{
	public function __invoke(Request $request, MenuRepository $menus): JsonResponse
	{
		return $menus
			->menu('SIDEBAR', (string)($request->get('casino_id') ?? config('casino.defaultCasinoId')))
			->response();
	}
}
