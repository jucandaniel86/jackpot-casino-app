<?php

	namespace App\Interfaces;

	use Illuminate\Http\Request;

	interface PlayersInterface
	{
		public function registration(Request $request): array;

		public function savePlayerProfile(Request $request): array;

		public function savePlayerSettings(Request $request): array;

		public function profile(Request $request);

		public function list(array $params = []): array;

		public function playerSessions(int $playerID): array;

		public function toggleGameFavorite($gameID): array;

		public function getFavGames(): array;

		public function playerBet(Request $request): array;
	}