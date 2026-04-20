<?php

	namespace App\Repositories\Crypto\Contracts;

	use App\Models\Session;
	use App\Models\Transaction;
	use App\Models\Wallet;

	interface TransactionWriterInterface
	{
		public function writeGameTransaction(
			Wallet  $wallet,
			Session $session,
			string  $type,
			string  $status,
			string  $amountBase,
			int     $decimals,
			array   $meta = []
		): void;

		public function writeDepositTransaction(
			Wallet  $wallet,
			string  $status,
			string  $amountBase,
			int     $decimals,
			string  $txid,
			?string $fromAddress = null,
			array   $meta = []
		): void;

		public function writeWithdrawTransaction(
			Wallet  $wallet,
			string  $status,
			string  $amountBase,
			int     $decimals,
			?string $toAddress,
			?string $txid = null,
			array   $meta = []
		): Transaction;
	}