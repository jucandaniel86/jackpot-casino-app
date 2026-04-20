<?php

	namespace App\Repositories\Crypto\Contracts;

	use App\Models\Session;
	use App\Models\Wallet;

	interface GameWalletServiceInterface
	{
		public function placeBet(Wallet $wallet, Session $session, string $amountBase, int $decimals, array $ctx): void;

		public function applyWin(Wallet $wallet, Session $session, string $amountBase, int $decimals, array $ctx): void;

		public function refundBet(
			Wallet $wallet,
			string $amountBase,
			int    $decimals,
			array  $ctx,
			string $direction = 'credit'
		): void;
	}