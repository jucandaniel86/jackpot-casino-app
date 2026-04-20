<?php

	namespace App\Marketing\Contracts;

	interface MarketingServiceInterface
	{
		public function overview(array $filters): array;

		public function cohorts(array $filters): array;

		public function games(array $filters): array;

		public function segments(array $filters): array;
 
		public function funnel(array $filters): array;
	}