<?php

	namespace App\Providers;

	use Illuminate\Support\ServiceProvider;

	use App\Repositories\Crypto\Support\CryptoRegistry;
	use App\Repositories\Crypto\Support\SecretBox;

	use App\Repositories\Crypto\Solana\Contracts\SolanaRpcClientInterface;
	use App\Repositories\Crypto\Solana\Services\SolanaRpcClient;

	use App\Repositories\Crypto\Solana\Services\SolanaWalletProvider;
	use App\Repositories\Crypto\Solana\Services\SolanaDepositScanner;

	class CryptoServiceProvider extends ServiceProvider
	{
		public function register(): void
		{
			// encrypt/decrypt helper pentru wallet.meta.secret_enc
			$this->app->singleton(SecretBox::class);

			// Solana RPC binding (interfață -> implementare)
			$this->app->bind(SolanaRpcClientInterface::class, function () {
				return new SolanaRpcClient(
					config('solana.rpc_url'),
					config('solana.commitment')
				);
			});

			// Registry: listezi toate integrările existente aici
			$this->app->singleton(CryptoRegistry::class, function ($app) {
				return new CryptoRegistry(
					walletProviders: [
						$app->make(SolanaWalletProvider::class),
						// + EvmWalletProvider etc. mai târziu
					],
					depositScanners: [
						$app->make(SolanaDepositScanner::class),
						// + EvmDepositScanner etc. mai târziu
					]
				);
			});

			$this->app->bind(
				\App\Repositories\Crypto\Contracts\WalletQueryServiceInterface::class,
				\App\Repositories\Crypto\Services\WalletQueryService::class
			);

			$this->app->bind(
				\App\Repositories\Crypto\Contracts\TransactionQueryServiceInterface::class,
				\App\Repositories\Crypto\Services\TransactionQueryService::class
			);

			$this->app->bind(
				\App\Repositories\Crypto\Contracts\TransactionWriterInterface::class,
				\App\Repositories\Crypto\Services\TransactionWriter::class);
			$this->app->bind(
				\App\Repositories\Crypto\Contracts\GameWalletServiceInterface::class,
				\App\Repositories\Crypto\Services\GameWalletService::class);

			$this->app->bind(
				\App\Repositories\Crypto\Contracts\TreasuryDistributionServiceInterface::class,
				\App\Repositories\Crypto\Services\TreasuryDistributionService::class
			);

			$this->app->bind(
				\App\Repositories\Crypto\Contracts\TreasuryServiceInterface::class,
				\App\Repositories\Crypto\Services\TreasuryService::class
			);

			$this->app->bind(
				\App\Repositories\Crypto\Contracts\SweepServiceInterface::class,
				\App\Repositories\Crypto\Solana\Services\SolanaSweepService::class
			);

			$this->app->bind(
				\App\Repositories\Crypto\Withdraw\Contracts\WithdrawRequestServiceInterface::class,
				\App\Repositories\Crypto\Withdraw\Services\WithdrawRequestService::class
			);

			$this->app->bind(
				\App\Repositories\Crypto\FX\Contracts\ExchangeRateServiceInterface::class,
				\App\Repositories\Crypto\FX\Services\ExchangeRateService::class
			);
		}
	}