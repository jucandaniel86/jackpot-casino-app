<?php

	namespace App\Repositories\Stats\Contracts;

	interface ConversionFunnelServiceInterface
	{
		/**
		 * filters:
		 * - from (ISO) required  (cohort start)
		 * - to (ISO) required    (cohort end)
		 * - currency_code (PEP) optional (pentru deposited + bets)
		 * - bets_10_threshold (default 10)
		 * - bets_100_threshold (default 100)
		 */
		public function report(array $filters): array;
	}