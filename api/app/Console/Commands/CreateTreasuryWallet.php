<?php

	namespace App\Console\Commands;

	use App\Repositories\Crypto\Services\WalletLedgerService;
	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Repositories\Crypto\Support\SecretBox;
	use App\Models\Wallet;
	use App\Models\WalletType;
	use Illuminate\Console\Command;
	use Illuminate\Support\Str;
	use Tuupola\Base58;

	class CreateTreasuryWallet extends Command
	{
		protected $signature = 'crypto:create-treasury-wallet 
			{address? : Treasury owner address (base58)} 
			{--currency= : Currency id, ex SOLANA:PEP (defaults to crypto.defaultCurrency)} 
			{--holder-type=treasury : Holder morph type} 
			{--holder-id=1 : Holder id} 
			{--name= : Wallet name override}
			{--generate-keypair : Generate a new Solana keypair (ignores address)}
			{--export= : Path to write keypair JSON (array of 64 bytes)}
			{--force : Overwrite export file if it exists}
			{--print-secret : Print SOLANA_TREASURY_SECRET_ENC to console}';

		protected $description = 'Create a treasury wallet record using a provided address';

		public function handle(WalletLedgerService $ledger, SecretBox $box): int
		{
			$generateKeypair = (bool)$this->option('generate-keypair');
			$address = trim((string)$this->argument('address'));

			$secretEnc = null;
			$exportPath = $this->option('export');
			$force = (bool)$this->option('force');

			if ($generateKeypair) {
				$b58 = new Base58(["characters" => Base58::BITCOIN]);
				$kp = sodium_crypto_sign_keypair();
				$secret = sodium_crypto_sign_secretkey($kp); // 64 bytes
				$public = sodium_crypto_sign_publickey($kp); // 32 bytes
				$address = $b58->encode($public);

				$secretEnc = $box->encrypt($secret);

				if ($exportPath) {
					if (!str_starts_with($exportPath, '/')) {
						$exportPath = storage_path('secret/' . ltrim($exportPath, '/'));
					}
					$exportDir = dirname($exportPath);
					if (!is_dir($exportDir)) {
						mkdir($exportDir, 0770, true);
					}
					if (file_exists($exportPath) && !$force) {
						$this->error("Export file exists: {$exportPath} (use --force to overwrite)");
						return 1;
					}
					$bytes = array_values(unpack('C*', $secret));
					file_put_contents($exportPath, json_encode($bytes));
				}
			} else {
				if ($address === '') {
					$this->error('Missing address. Provide address or use --generate-keypair.');
					return 1;
				}
			}

			$currencyId = (string)($this->option('currency') ?: config('crypto.defaultCurrency'));
			if ($currencyId === '') {
				$this->error('Missing currency. Set --currency=SOLANA:PEP or crypto.defaultCurrency.');
				return 1;
			}

			[$network, $currencyCode] = str_contains($currencyId, ':')
				? explode(':', $currencyId, 2)
				: [null, $currencyId];

			$holderType = (string)$this->option('holder-type');
			$holderId = (int)$this->option('holder-id');
			$name = (string)($this->option('name') ?: ('treasury_' . $currencyCode));

			$existing = Wallet::query()
				->where('currency', $currencyId)
				->where('meta->owner_address', $address)
				->first();

			if ($existing) {
				$this->info("Treasury wallet already exists: id={$existing->id}");
				return 0;
			}

			$walletType = WalletType::query()
				->where('currency_id', $currencyId)
				->orWhere('code', $currencyCode)
				->orWhere('currency_code', $currencyCode)
				->first();

			if (!$walletType) {
				$this->error("Wallet type not found for currency={$currencyId} (code={$currencyCode}).");
				return 2;
			}

			$wallet = Wallet::create([
				'holder_type' => $holderType,
				'holder_id' => $holderId,
				'name' => $name,
				'wallet_type_id' => $walletType->id,
				'uuid' => (string)Str::uuid(),
				'balance' => 0,
				'currency' => $currencyId,
				'currency_id' => $currencyId,
				'currency_code' => $currencyCode,
				'network' => $network,
			]);

			$mint = config("crypto.currencies.{$currencyId}.mint");
			$decimals = CurrencyDecimals::internalForCurrency($currencyId);

			$meta = [
				'chain' => $network ? strtolower($network) : null,
				'owner_address' => $address,
				'token_mint' => $mint,
				'token_account' => null,
				'last_signature' => null,
				'decimals' => $decimals,
			];
			if ($secretEnc) {
				$meta['secret_enc'] = $secretEnc;
			}
			$wallet->meta = $meta;
			$wallet->save();

			$ledger->ensureBalanceRow($wallet);

			$this->info("Treasury wallet created: id={$wallet->id} currency={$currencyId}");
			$this->line("Set TREASURY_WALLET_ID_{$network}_{$currencyCode}={$wallet->id} in .env if needed.");
			$this->line("Set SOLANA_TREASURY_OWNER={$address} in .env");
			if ($secretEnc) {
				if ((bool)$this->option('print-secret')) {
					$this->line("Set SOLANA_TREASURY_SECRET_ENC={$secretEnc} in .env");
				} else {
					$this->line("Treasury secret generated. Use --print-secret to display SOLANA_TREASURY_SECRET_ENC.");
				}
			}
			if ($exportPath) {
				$this->line("Keypair exported to {$exportPath}");
			}

			return 0;
		}
	}
