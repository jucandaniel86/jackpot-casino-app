<?php

	namespace App\Repositories\Integrations\Contracts;

	use App\Models\Bet;
	use App\Models\Session;

	interface BetsWriterInterface
	{
		public function writePlaceBet(
			Session $session,
			string  $gameID,
			string  $transactionID,
			string  $roundID,
			string  $currency,
			string  $ts,
			int     $amount,
			int     $roundFinished,
		): Bet;

		public function writeWin(
			Session $session,
			string  $gameID,
			string  $transactionID,
			string  $roundID,
			string  $currency,
			string  $ts,
			int     $amount,
			int     $roundFinished,
		): Bet;

		public function refund(
			Bet    $transaction,
			string $roundID,
			string $ts,
			string $roundFinished,
			int    $balanceBefore,
			int    $balanceAfter,
			string $transactionType
		): string;
	}