<?php

	namespace App\Repositories\Stats\Contracts;

	interface DashboardStatsServiceInterface
	{
		/**
		 * Returnează KPI-uri pentru dashboard.
		 * $currencyId optional (ex: SOLANA:PEP) ca să filtrezi.
		 */
		public function today(array $filters = []): array;
	}