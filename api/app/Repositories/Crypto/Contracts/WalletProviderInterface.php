<?php

	namespace App\Repositories\Crypto\Contracts;

	use App\Models\Wallet;
	use App\Repositories\Crypto\DTO\CreateWalletResult;

	interface WalletProviderInterface
	{
		public function supports(string $currency): bool;

		public function createWallet(Wallet $wallet): CreateWalletResult;
	}