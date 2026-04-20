<?php

	namespace App\Repositories\Crypto\Contracts;

	use App\Models\Wallet;
	use App\Repositories\Crypto\DTO\ScanResult;

	interface DepositScannerInterface
	{
		public function supports(string $currency): bool;

		public function scan(Wallet $wallet): ScanResult;
	}