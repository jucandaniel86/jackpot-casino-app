<?php

	namespace App\Repositories\Stats\Contracts;

	interface CryptoOpsDashboardServiceInterface
	{
		public function sweepsReport(array $filters): array;
	}