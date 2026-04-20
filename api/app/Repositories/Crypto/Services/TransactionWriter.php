<?php

	namespace App\Repositories\Crypto\Services;

	use App\Repositories\Crypto\Contracts\TransactionWriterInterface;
	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Repositories\Crypto\Support\Money;
	use App\Models\Session;
	use App\Models\Transaction;
	use App\Models\Wallet;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Str;

	class TransactionWriter implements TransactionWriterInterface
	{
		public function writeGameTransaction(
			Wallet  $wallet,
			Session $session,
			string  $type,
			string  $status,
			string  $amountBase,
			int     $decimals,
			array   $meta = []
		): void
		{
			$uiDecimals = CurrencyDecimals::uiForWallet($wallet);
			Transaction::create([
				'wallet_id' => $wallet->id,
				'uuid' => (string)Str::uuid(),
				'currency' => $wallet->currency,
				'currency_id' => $wallet->currency_id,
				'currency_code' => $wallet->currency_code,
				'network' => $wallet->network,
				'type' => $type,
				'status' => $status,
				'amount_base' => $amountBase,
				'decimals' => $decimals,
				'amount' => Money::baseToUi($amountBase, $decimals, $uiDecimals),
				'to_address' => null,
				'txid' => null,
				'meta' => $meta,
				'int_casino_id' => $session->int_casino_id
			]);
		}

		public function writeDepositTransaction(Wallet $wallet, string $status, string $amountBase, int $decimals, string $txid, ?string $fromAddress = null, array $meta = []): void
		{
			$intCasinoId = $wallet->holder->int_casino_id ?? null;
			$uiDecimals = CurrencyDecimals::uiForWallet($wallet);
			$tx = Transaction::create([
				'wallet_id' => $wallet->id,
				'currency' => $wallet->currency,
				'currency_id' => $wallet->currency_id,
				'currency_code' => $wallet->currency_code,
				'network' => $wallet->network,
				'uuid' => (string)\Illuminate\Support\Str::uuid(),
				'type' => 'deposit',
				'status' => $status,
				'amount_base' => $amountBase,
				'decimals' => $decimals,
				'amount' => Money::baseToUi($amountBase, $decimals, $uiDecimals),
				'txid' => $txid,
				'from_address' => $fromAddress, // dacă ai coloana; altfel bagă în meta
				'to_address' => $wallet->meta['owner_address'] ?? null,
				'meta' => $meta,
				'int_casino_id' => $intCasinoId
			]);

			$tx->credited_at = now();
			$tx->save();
		}

		public function writeWithdrawTransaction(
			Wallet  $wallet,
			string  $status,
			string  $amountBase,
			int     $decimals,
			?string $toAddress,
			?string $txid = null,
			array   $meta = []
		): Transaction
		{
			$intCasinoId = $wallet->holder->int_casino_id ?? null;
			$uiDecimals = CurrencyDecimals::uiForWallet($wallet);
			return Transaction::create([
				'wallet_id' => $wallet->id,
				'uuid' => (string)Str::uuid(),
				'currency' => $wallet->currency,
				'currency_id' => $wallet->currency_id,
				'currency_code' => $wallet->currency_code,
				'network' => $wallet->network,
				'type' => 'withdraw',
				'status' => $status,
				'amount_base' => $amountBase,
				'decimals' => $decimals,
				'amount' => Money::baseToUi($amountBase, $decimals, $uiDecimals),
				'txid' => $txid,
				'from_address' => $wallet->meta['owner_address'] ?? null,
				'to_address' => $toAddress,
				'meta' => $meta,
				'block_time' => $meta['block_time'] ?? null,
				'int_casino_id' => $intCasinoId,
			]);
		}
	}
