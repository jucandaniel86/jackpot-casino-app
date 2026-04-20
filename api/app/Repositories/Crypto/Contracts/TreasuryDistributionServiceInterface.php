<?php

	namespace App\Repositories\Crypto\Contracts;

	interface TreasuryDistributionServiceInterface
	{
		/**
		 * Returnează 2 slice-uri: casino vs users.
		 * $currencyId = ex "SOLANA:PEP"
		 *
		 * Return:
		 * [
		 *   ['label' => 'Casino wallet', 'amount' => '1.00000000', 'percent' => '20.00'],
		 *   ['label' => 'User wallets',  'amount' => '4.00000000', 'percent' => '80.00'],
		 * ]
		 */
		public function getDistribution(string $currencyId, int $scale = -1, ?string $intCasinoId = null): array;
	}
