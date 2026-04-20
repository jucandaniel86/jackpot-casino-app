<?php

	namespace App\Jobs;

	use App\Repositories\Crypto\Contracts\SweepServiceInterface;
	use App\Models\Wallet;
	use Illuminate\Bus\Queueable;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Foundation\Bus\Dispatchable;
	use Illuminate\Queue\InteractsWithQueue;
	use Illuminate\Queue\SerializesModels;
	use Illuminate\Support\Facades\Log;

	class SweepSolanaJob implements ShouldQueue
	{
		use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

		public function __construct(public int $walletId)
		{
		}

		public function handle(SweepServiceInterface $sweep): void
		{
			$wallet = Wallet::find($this->walletId);
			if (!$wallet) return;

			if (!$sweep->supports($wallet->currency)) return;

			try {
				$txid = $sweep->sweep($wallet->id);

				if ($txid) {
					Log::info('Sweep submitted', [
						'wallet_id' => $wallet->id,
						'txid' => $txid,
					]);
					ConfirmSolanaSweepJob::dispatch($txid)
						->delay(now()->addSeconds(15))
						->onQueue('crypto');

				}
			} catch (\Throwable $e) {
				Log::error('Sweep failed', [
					'wallet_id' => $wallet->id,
					'error' => $e->getMessage(),
				]);
				throw $e; // retry
			}
		}
	}