<?php

	namespace App\Repositories\Stats\Contracts;

	interface CryptoOpsStatsServiceInterface
	{
		public function report(array $filters): array;
	}