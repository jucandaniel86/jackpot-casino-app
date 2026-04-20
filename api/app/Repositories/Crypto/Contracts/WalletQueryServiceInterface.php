<?php

	namespace App\Repositories\Crypto\Contracts;

	use App\Repositories\Crypto\DTO\WalletView;
	use App\Models\Player;

	interface WalletQueryServiceInterface
	{
		public function getActiveWalletsForHolder(string $holderType, int $holderId): array;

		/**
		 * Setează current_wallet_id pe player, doar dacă wallet-ul:
		 * - aparține player-ului
		 * - este activ (wallet_types.active=1)
		 */
		public function setCurrentWalletForPlayer(Player $player, int $walletId): void;

		/**
		 * Returnează wallet-ul curent (în același format ca listarea),
		 * sau null dacă nu este setat / nu e valid.
		 */
		public function getCurrentWalletForPlayer(Player $player): ?WalletView;
	}