<?php

	namespace App\Repositories\Stats\Contracts;

	interface GgrReportServiceInterface
	{
		/**
		 * filters:
		 * - from (ISO) required
		 * - to (ISO) required
		 * - group_by: currency|provider|game (default currency)
		 * - currency_code (e.g. PEP) optional
		 * - provider_id optional
		 * - game_id optional (provider game_id)
		 */
		public function report(array $filters): array;
	}