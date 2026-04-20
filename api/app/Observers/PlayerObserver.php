<?php

	namespace App\Observers;

	use App\Repositories\Crypto\Support\CryptoRegistry;
	use App\Models\Player;
	use App\Models\Wallet;
	use App\Models\WalletType;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Str;

	class PlayerObserver
	{
		public function created(Player $user): void
		{
			Log::channel('crypto')->info('crypto wallets init');
			try {
				$walletTypes = WalletType::query()
					->where('active', 1)
					->orderByRaw("CASE WHEN purpose = 'real' THEN 0 ELSE 1 END")
					->orderBy('id')
					->get();

				/** @var \App\Repositories\Crypto\Support\CryptoRegistry $registry */
				$registry = app(\App\Repositories\Crypto\Support\CryptoRegistry::class);

				/** @var \App\Repositories\Crypto\Services\WalletLedgerService $ledger */
				$ledger = app(\App\Repositories\Crypto\Services\WalletLedgerService::class);

				$defaultCurrency = config('crypto.defaultCurrency');
				$defaultWallet = null;

				foreach ($walletTypes as $item) {
					$purpose = (string)($item->purpose ?? 'real');

					// 1) Evită duplicate per wallet type
					$existing = Wallet::query()
						->where('holder_type', $user->getMorphClass())
						->where('holder_id', $user->id)
						->where('wallet_type_id', $item->id)
						->first();

					if ($existing) {
						if ($purpose === 'real' && (string)$item->currency_id === (string)$defaultCurrency) {
							$defaultWallet = $existing;
						}
						continue;
					}
					Log::channel('crypto')->info('crypto wallets init', [
						'currency' => $item->code,
						'purpose' => $purpose,
					]);
					// 2) Creează wallet row (virtual wallet în sistem)
					$wallet = Wallet::create([
						'holder_type' => $user->getMorphClass(),
						'holder_id' => $user->id,
						'name' => $user->fixed_id . '_' . ($item->currency_code ?? $item->code) . '_' . $purpose,
						'wallet_type_id' => $item->id,
						'uuid' => (string)Str::uuid(),
						'meta' => ['purpose' => $purpose],
						'balance' => 0,
						'currency' => $item->currency_id,
						'currency_id' => $item->currency_id,
						'currency_code' => $item->currency_code ?: $item->code,
						'network' => $item->network,
					]);

					if ($purpose === 'real' && (string)$item->currency_id === (string)$defaultCurrency) {
						$defaultWallet = $wallet;
					}

					$shouldInitProvider = $purpose !== 'bonus' && (int)$item->is_fiat !== 1;

					if ($shouldInitProvider) {
						// 3) Inițializează wallet meta folosind providerul corect (SolanaWalletProvider)
						/** @var CryptoRegistry $registry */
						$provider = $registry->tryWalletProvider($wallet->currency);

						if (!$provider) {
							Log::channel('crypto')->warning('No wallet provider implemented, skipping crypto init', [
								'currency' => $wallet->currency,
								'wallet_type_id' => $item->id,
							]);
						} else {
							$provider = $registry->walletProvider($wallet->currency);

							$res = $provider->createWallet($wallet);
							Log::channel('crypto')->info('Wallet PEP INFO', $res->meta);
							// Nota: res->meta conține owner_address, secret_enc, token_mint, etc.
							$wallet->meta = array_merge($wallet->meta ?? [], $res->meta ?? []);
							$wallet->save();
						}
					}

					// 4) Creează snapshot row în wallet_balances (și pentru bonus)
					$ledger->ensureBalanceRow($wallet);

					Log::channel('crypto')->info('Wallet created for user', [
						'user_id' => $user->id,
						'wallet_id' => $wallet->id,
						'currency' => $wallet->currency,
						'purpose' => $purpose,
						'owner_address' => $wallet->meta['owner_address'] ?? null,
					]);
				}

				// Set default wallet on player
				if ($defaultWallet) {
					$user->current_wallet_id = $defaultWallet->id;
					$user->save();

					Log::channel('crypto')->info('Default wallet assigned', [
						'user_id' => $user->id,
						'wallet_id' => $defaultWallet->id,
						'currency' => $defaultWallet->currency,
					]);
				}

				if (!$defaultWallet) {
					Log::channel('crypto')->warning('Default currency not found when creating player', [
						'player_id' => $user->id,
						'default_currency' => $defaultCurrency,
					]);
				}

			} catch (\Throwable $e) {
				// În producție, vrei să știi dacă userul nu primește wallet (altfel nu poate depune)
				Log::channel('crypto')->error('Failed to create wallet for user', [
					'user_id' => $user->id,
					'error' => $e->getMessage(),
				]);
			}
		}
	}
