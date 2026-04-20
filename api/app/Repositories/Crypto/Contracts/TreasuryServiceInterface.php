<?php

	namespace App\Repositories\Crypto\Contracts;

	use App\Models\Wallet;

	interface TreasuryServiceInterface
	{
		public function getTreasuryWallet(string $currencyKey): Wallet;
	}