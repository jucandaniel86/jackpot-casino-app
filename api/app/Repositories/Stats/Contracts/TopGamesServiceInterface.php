<?php

	namespace App\Repositories\Stats\Contracts;

	interface TopGamesServiceInterface
	{
		/**
		 * filters:
		 * - from (ISO) optional
		 * - to (ISO) optional
		 * - currency_code (e.g. PEP) optional
		 * - provider_id optional
		 * - limit optional
		 */
		public function topGames(array $filters = []): array;
	}