<?php

	namespace App\Console\Commands;

	use Illuminate\Console\Command;
	use App\Models\Wallet;
	use App\Repositories\Crypto\Support\SecretBox;
	use Tuupola\Base58;

	class VerifyCryptoWallet extends Command
	{
		protected $signature = 'crypto:verify-wallet {wallet_id}';
		protected $description = 'Verify that wallet.meta.secret_enc matches wallet.meta.owner_address';

		public function handle(): int
		{
			$id = (int)$this->argument('wallet_id');
			$wallet = Wallet::find($id);

			if (!$wallet) {
				$this->error("Wallet not found: {$id}");
				return 1;
			}

			$meta = $wallet->meta ?? [];
			$owner = $meta['owner_address'] ?? null;
			$secretEnc = $meta['secret_enc'] ?? null;

			if (!$owner || !$secretEnc) {
				$this->error("Missing owner_address or secret_enc in meta");
				return 1;
			}

			$secret = app(SecretBox::class)->decrypt($secretEnc);
			$public = sodium_crypto_sign_publickey_from_secretkey($secret);

			$b58 = new Base58(["characters" => Base58::BITCOIN]);
			$derived = $b58->encode($public);

			$ok = hash_equals($owner, $derived);

			$this->line("owner_address : {$owner}");
			$this->line("derived       : {$derived}");
			$this->info($ok ? "OK ✅ Keys match" : "FAIL ❌ Keys DO NOT match");

			return $ok ? 0 : 2;
		}
	}
