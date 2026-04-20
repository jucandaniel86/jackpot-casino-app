<?php

	namespace App\Jobs;

	use Illuminate\Bus\Queueable;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Foundation\Bus\Dispatchable;
	use Illuminate\Queue\SerializesModels;
	use App\Repositories\Crypto\Support\CryptoRegistry;
	use App\Models\Wallet;
	use Illuminate\Support\Facades\Log;

	class CryptoScanShardJob implements ShouldQueue
	{
		use Queueable, SerializesModels, Dispatchable;

		public function __construct(
			public string $currency,
			public int    $shardIndex,
			public int    $shardCount
		)
		{
		}

		public function handle(CryptoRegistry $registry): void
		{
			$scanner = $registry->depositScanner($this->currency);

			Log::channel('crypto')->info("crypto deposit scaner run()");

			Wallet::query()
				->where('currency', $this->currency)
				->whereRaw('(id % ?) = ?', [$this->shardCount, $this->shardIndex])
				->orderBy('id')
				->chunkById(500, function ($wallets) use ($scanner) {
					foreach ($wallets as $w) {
						$scanner->scan($w);
					}
				});
		}
	}