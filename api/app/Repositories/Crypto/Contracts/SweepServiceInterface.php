<?php

	namespace App\Repositories\Crypto\Contracts;

	interface SweepServiceInterface
	{
		public function supports(string $currency): bool;

		/**
		 * Sweep funds from a user deposit wallet to treasury.
		 * Returns txid (signature) or null if nothing to sweep.
		 */
		public function sweep(int $walletId): ?string;
	}