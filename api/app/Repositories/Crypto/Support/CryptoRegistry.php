<?php

	namespace App\Repositories\Crypto\Support;

	use App\Repositories\Crypto\Contracts\WalletProviderInterface;
	use App\Repositories\Crypto\Contracts\DepositScannerInterface;

	class CryptoRegistry
	{
		/** @param WalletProviderInterface[] $walletProviders
		 * @param DepositScannerInterface[] $depositScanners
		 */
		public function __construct(
			private array $walletProviders,
			private array $depositScanners
		)
		{
		}

		public function hasWalletProvider(string $currency): bool
		{
			foreach ($this->walletProviders as $p) {
				if ($p->supports($currency)) return true;
			}
			return false;
		}

		public function tryWalletProvider(string $currency): ?\App\Repositories\Crypto\Contracts\WalletProviderInterface
		{
			foreach ($this->walletProviders as $p) {
				if ($p->supports($currency)) return $p;
			}
			return null;
		}

		public function walletProvider(string $currency): WalletProviderInterface
		{
			foreach ($this->walletProviders as $p) {
				if ($p->supports($currency)) return $p;
			}
			throw new \RuntimeException("No WalletProvider for currency={$currency}");
		}

		public function depositScanner(string $currency): DepositScannerInterface
		{
			foreach ($this->depositScanners as $s) {
				if ($s->supports($currency)) return $s;
			}
			throw new \RuntimeException("No DepositScanner for currency={$currency}");
		}
	}