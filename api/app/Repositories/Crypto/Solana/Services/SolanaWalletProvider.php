<?php

	namespace App\Repositories\Crypto\Solana\Services;

	use Tuupola\Base58;
	use App\Repositories\Crypto\Contracts\WalletProviderInterface;
	use App\Repositories\Crypto\Support\SecretBox;
	use App\Repositories\Crypto\DTO\CreateWalletResult;
	use App\Models\Wallet;

	class SolanaWalletProvider implements WalletProviderInterface
	{
		public function supports(string $currency): bool
		{
			return str_starts_with($currency, 'SOLANA:');
		}

		public function createWallet(Wallet $wallet): CreateWalletResult
		{
			$b58 = new Base58(["characters" => Base58::BITCOIN]);

			$kp = sodium_crypto_sign_keypair();
			$secret = sodium_crypto_sign_secretkey($kp); // 64 bytes
			$public = sodium_crypto_sign_publickey($kp); // 32 bytes

			$address = $b58->encode($public);

			$mint = config("crypto.currencies.{$wallet->currency}.mint");
			if (!$mint) throw new \RuntimeException("Missing mint for {$wallet->currency}");

			$secretEncB64 = app(SecretBox::class)->encrypt($secret);

			return new CreateWalletResult($address, [
				'chain' => 'solana',
				'owner_address' => $address,
				'secret_enc' => $secretEncB64,
				'token_mint' => $mint,
				'token_account' => null,
				'last_signature' => null,
				'decimals' => null,
			]);
		}
	}