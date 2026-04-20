<?php

	namespace App\Repositories\Integrations\Services;

	use App\Enums\TransactionTypes;
	use App\Repositories\Integrations\Contracts\BetsWriterInterface;
	use App\Models\Bet;
	use App\Models\Session;
	use Illuminate\Support\Str;

	class BetsWriterService implements BetsWriterInterface
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
		): Bet
		{
			return Bet::create([
				'transaction_id' => Str::uuid(),
				'session_id' => $session->session,
				'wallet_id' => $session->wallet_id,
				'user_id' => $session->user_id,
				'game_id' => $gameID,
				'operator_transaction_id' => $transactionID,
				'operator_round_id' => $roundID,
				'currency' => $currency,
				'ts' => $ts,
				'refund_ts' => 0,
				'balance_before' => 0,
				'balance_after' => 0,
				'stake' => $amount,
				'payout' => 0,
				'refund' => 0,
				'transaction_type' => TransactionTypes::BET->value,
				'round_finished' => $roundFinished,
				'when_placed' => now(),
				'int_casino_id' => $session->int_casino_id,
			]);
		}

		public function writeWin(
			Session $session,
			string  $gameID,
			string  $transactionID,
			string  $roundID,
			string  $currency,
			string  $ts,
			int     $amount,
			int     $roundFinished,
		): Bet
		{
			return Bet::create([
				'transaction_id' => Str::uuid(),
				'session_id' => $session->session,
				'wallet_id' => $session->wallet_id,
				'user_id' => $session->user_id,
				'game_id' => $gameID,
				'operator_transaction_id' => $transactionID,
				'operator_round_id' => $roundID,
				'currency' => $currency,
				'ts' => $ts,
				'refund_ts' => 0,
				'balance_before' => 0,
				'balance_after' => 0,
				'stake' => 0,
				'payout' => $amount,
				'refund' => 0,
				'transaction_type' => TransactionTypes::WIN->value,
				'round_finished' => $roundFinished,
				'when_placed' => now(),
				'int_casino_id' => $session->int_casino_id,
			]);
		}

		public function refund(
			Bet    $transaction,
			string $roundID,
			string $ts,
			string $roundFinished,
			int    $balanceBefore,
			int    $balanceAfter,
			string $transactionType
		): string
		{
			$RefundTransaction = Bet::create([
				'transaction_id' => Str::uuid(),
				'session_id' => $transaction->session_id,
				'wallet_id' => $transaction->wallet_id,
				'user_id' => $transaction->user_id,
				'game_id' => $transaction->game_id,
				'operator_transaction_id' => $transaction->operator_transaction_id,
				'operator_round_id' => $roundID,
				'currency' => $transaction->currency,
				'ts' => $ts,
				'refund_ts' => 0,
				'balance_before' => $balanceBefore,
				'balance_after' => $balanceAfter,
				'stake' => $transactionType == TransactionTypes::WIN->value ? $transaction->stake : 0,
				'payout' => $transactionType == TransactionTypes::BET->value ? $transaction->stake : 0,
				'refund_value' => 0,
				'transaction_type' => TransactionTypes::REFUND->value,
				'round_finished' => $roundFinished,
				'when_placed' => now(),
			]);

			$transaction->update([
				'refund_transaction_id' => $RefundTransaction->transaction_id,
				'refund' => $transaction->stake
			]);

			return $RefundTransaction->transaction_id;
		}
	}