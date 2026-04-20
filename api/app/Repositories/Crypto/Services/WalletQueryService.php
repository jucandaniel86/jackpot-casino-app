<?php

	namespace App\Repositories\Crypto\Services;

	use App\Repositories\Crypto\Contracts\WalletQueryServiceInterface;
	use App\Repositories\Crypto\DTO\WalletView;
	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Models\Player;
	use App\Models\WalletBalance;
	use App\Models\Wallet;
	use App\Models\WalletType;
	use Illuminate\Support\Facades\DB;

	class WalletQueryService implements WalletQueryServiceInterface
	{
		public function getActiveWalletsForHolder(string $holderType, int $holderId): array
		{
			$blockedCurrencies = $this->getWithdrawBlockedCurrenciesForHolder($holderType, $holderId);

			$activeTypes = WalletType::query()
				->where('active', 1)
				->get()
				->keyBy('id');

			$wallets = Wallet::query()
				->where('holder_type', $holderType)
				->where('holder_id', $holderId)
				->whereIn('wallet_type_id', $activeTypes->keys())
				->get();

			$balances = WalletBalance::query()
				->whereIn('wallet_id', $wallets->pluck('id'))
				->get()
				->keyBy('wallet_id');

			$out = [];
			foreach ($wallets as $w) {
				$t = $activeTypes[$w->wallet_type_id] ?? null;
				$b = $balances[$w->id] ?? null;

				$decimals = CurrencyDecimals::internalForWallet($w);
				$canWithdraw = !isset($blockedCurrencies[$w->currency]);

				$out[] = new WalletView($w, $t, $b, $decimals, $canWithdraw);
			}

			usort($out, fn($a, $b) => strcmp($a->wallet->currency, $b->wallet->currency));
			return $out;
		}

		public function setCurrentWalletForPlayer(Player $player, int $walletId): void
		{
			DB::transaction(function () use ($player, $walletId) {
				/** @var Wallet|null $wallet */
				$wallet = Wallet::query()
					->where('id', $walletId)
					->where('holder_type', $player->getMorphClass())
					->where('holder_id', $player->id)
					->first();

				if (!$wallet) {
					throw new \RuntimeException('Wallet not found for this player');
				}

				// wallet type trebuie să fie activ
				$type = WalletType::query()
					->where('id', $wallet->wallet_type_id)
					->where('active', 1)
					->where('purpose', 'real')
					->first();

				if (!$type) {
					throw new \RuntimeException('Wallet type is not active');
				}

				$player->current_wallet_id = $wallet->id;
				$player->save();
			});
		}

		public function getCurrentWalletForPlayer(Player $player): ?WalletView
		{
			$walletId = (int)($player->current_wallet_id ?? 0);
			if ($walletId <= 0) return null;

			$wallet = Wallet::query()
				->where('id', $walletId)
				->where('holder_type', $player->getMorphClass())
				->where('holder_id', $player->id)
				->first();

			if (!$wallet) return null;

			$type = WalletType::query()
				->where('id', $wallet->wallet_type_id)
				->where('active', 1)
				->first();

			if (!$type) return null;

			$balance = WalletBalance::query()
				->where('wallet_id', $wallet->id)
				->first();

			$decimals = CurrencyDecimals::internalForWallet($wallet);
			$blockedCurrencies = $this->getWithdrawBlockedCurrenciesForHolder($player->getMorphClass(), (int)$player->id);
			$canWithdraw = !isset($blockedCurrencies[$wallet->currency]);

			return new WalletView($wallet, $type, $balance, $decimals, $canWithdraw);
		}

		private function getWithdrawBlockedCurrenciesForHolder(string $holderType, int $holderId): array
		{
			if ($holderType !== Player::class) return [];

			$todayStart = now()->startOfDay();

			$currencies = DB::table('withdraw_requests')
				->where('player_id', $holderId)
				->where('created_at', '>=', $todayStart)
				->pluck('currency')
				->unique()
				->all();

			return array_fill_keys($currencies, true);
		}
	}
