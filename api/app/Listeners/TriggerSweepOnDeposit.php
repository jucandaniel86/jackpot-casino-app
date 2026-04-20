<?php

namespace App\Listeners;

use App\Events\DepositDetected;
use App\Jobs\SweepSolanaJob;
use Illuminate\Support\Facades\Log;

class TriggerSweepOnDeposit
{
	public function handle(DepositDetected $event): void
	{
		$wallet = $event->wallet;
		if (!$wallet) {
			return;
		}

		if ($wallet->holder_type === \App\Models\Casino::class) {
			return;
		}

		$currency = (string)$wallet->currency;
		if (!str_starts_with($currency, 'SOLANA:')) {
			return;
		}

		SweepSolanaJob::dispatch($wallet->id)->onQueue('crypto');
		Log::channel('crypto')->info('Sweep queued on deposit', [
			'wallet_id' => $wallet->id,
			'currency' => $currency,
			'txid' => $event->txid ?? null,
		]);
	}
}
