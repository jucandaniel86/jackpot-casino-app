<?php

	namespace App\Http\Controllers\FE;

	use App\Http\Controllers\Controller;
	use App\Repositories\TournamentLeaderboardRepository;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;

	class TournamentLeaderboardController extends Controller
	{
		public function leaderboard(string $id, Request $request, TournamentLeaderboardRepository $repo): JsonResponse
		{
			$validated = $request->validate([
				'limit' => 'nullable|integer|min:1|max:200',
			]);

			$limit = (int)($validated['limit'] ?? 20);

			$data = $repo->leaderboard($id, $limit);

			return response()->json([
				'success' => true,
				'data' => $data,
			]);
		}

		public function standing(string $id, Request $request, TournamentLeaderboardRepository $repo): JsonResponse
		{
			$user = $request->user();

			$data = $repo->userStanding($id, (int)$user->id);

			return response()->json([
				'success' => true,
				'data' => $data,
			]);
		}
	}

