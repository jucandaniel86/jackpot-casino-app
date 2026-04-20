<?php

	namespace App\Repositories\Crypto\Services;

	use App\Repositories\Crypto\Contracts\TreasuryServiceInterface;
	use App\Models\Wallet;

	class TreasuryService implements TreasuryServiceInterface
	{
		public function getTreasuryWallet(string $currencyKey): Wallet
		{
			// currencyKey e ex "SOLANA:PEP" -> symbol "PEP"
			$symbol = str_contains($currencyKey, ':') ? explode(':', $currencyKey, 2)[1] : $currencyKey;

			$id = config("crypto.treasury_wallet_ids.{$symbol}");
			if (!$id) {
				throw new \RuntimeException("Missing treasury wallet id in config for {$symbol}");
			}

			$wallet = Wallet::query()->find((int)$id);
			if (!$wallet) {
				throw new \RuntimeException("Treasury wallet not found id={$id}");
			}

			return $wallet;
		}
	}