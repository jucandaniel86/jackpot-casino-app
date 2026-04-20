<?php

	namespace App\Repositories\Stats\Contracts;

	interface GamesTabServiceInterface
	{
		/**
		 * Returnează date pentru Games tab.
		 * filters:
		 * - from (ISO) optional (default: last 7 days)
		 * - to (ISO) optional (default: now)
		 * - currency_code (PEP) optional
		 * - provider_id optional
		 * - limit optional (default 10)
		 * - order_by: bets|wagered (default bets)
		 */
		public function summary(array $filters = []): array;
	}