<?php

	namespace App\Repositories\Stats\Contracts;

	interface FinanceOpsDashboardServiceInterface
	{
		/**
		 * filters:
		 * - from (ISO) required
		 * - to (ISO) required
		 * - currency_code (ex: PEP) optional
		 * - unclaimed_days (default 7)
		 */
		public function report(array $filters): array;
	}