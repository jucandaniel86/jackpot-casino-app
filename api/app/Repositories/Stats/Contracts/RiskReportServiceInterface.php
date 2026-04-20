<?php

	namespace App\Repositories\Stats\Contracts;

	interface RiskReportServiceInterface
	{
		public function overview(array $filters): array;

		public function players(array $filters): array;

		public function duplicates(array $filters): array;

		// optional (phase 2) - MVP
		public function gameAbuse(array $filters): array;
	}