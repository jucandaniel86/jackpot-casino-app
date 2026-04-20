<?php

	namespace App\Jobs;

	use App\Repositories\Crypto\Solana\Contracts\SolanaRpcClientInterface;
	use Illuminate\Bus\Queueable;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Foundation\Bus\Dispatchable;
	use Illuminate\Queue\InteractsWithQueue;
	use Illuminate\Queue\SerializesModels;
	use Illuminate\Support\Facades\DB;

	class ConfirmSolanaSweepJob implements ShouldQueue
	{
		use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

		public function __construct(public string $txid)
		{
		}

		public function handle(SolanaRpcClientInterface $rpc): void
		{
			$statuses = $rpc->getSignatureStatuses([$this->txid]);
			$info = $statuses['value'][0] ?? null;

			if (!$info) return;

			$status = $info['confirmationStatus'] ?? null;
			if ($status === 'confirmed' || $status === 'finalized') {
				DB::table('transaction')
					->where('txid', $this->txid)
					->update([
						'status' => 'confirmed',
						'block_time' => $info['slot'] ?? null,
						'updated_at' => now(),
					]);
			}

			if (!empty($info['err'])) {
				DB::table('transaction')
					->where('txid', $this->txid)
					->update([
						'status' => 'failed',
						'updated_at' => now(),
					]);
			}
		}
	}
