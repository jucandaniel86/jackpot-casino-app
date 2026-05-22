<?php

namespace App\Http\Controllers\FE;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Repositories\MenuRepository;
use App\Traits\ContainerGeneratorTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
	use ContainerGeneratorTrait;

	public function show(string $id, Request $request, MenuRepository $menus): JsonResponse
	{
		$tournament = Tournament::query()
			->with(['tournamentGames.game', 'games', 'prizes', 'prizeAwards'])
			->find($id);

		if (!$tournament) {
			return response()->json([
				'success' => false,
				'message' => 'Tournament not found.',
				'data' => [
					'sidebar' => $menus->menu('SIDEBAR', $this->casinoId($request)),
					'tournament' => null,
				],
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Tournament fetched successfully.',
			'data' => [
				'sidebar' => $menus->menu('SIDEBAR', $this->casinoId($request)),
				'tournament' => $this->formatTournamentForFrontend($tournament),
			],
		]);
	}

	private function casinoId(Request $request): string
	{
		return (string)($request->get('casino_id') ?? config('casino.defaultCasinoId'));
	}
}
