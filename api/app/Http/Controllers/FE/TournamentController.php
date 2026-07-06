<?php

namespace App\Http\Controllers\FE;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Traits\ContainerGeneratorTrait;
use Illuminate\Http\JsonResponse;

class TournamentController extends Controller
{
	use ContainerGeneratorTrait;

	public function show(string $id): JsonResponse
	{
		$tournament = Tournament::query()
			->with(['tournamentGames.game', 'games', 'prizes', 'prizeAwards'])
			->find($id);

		if (!$tournament) {
			return response()->json([
				'success' => false,
				'message' => 'Tournament not found.',
				'data' => [
					'tournament' => null,
				],
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Tournament fetched successfully.',
			'data' => [
				'tournament' => $this->formatTournamentForFrontend($tournament),
			],
		]);
	}
}
