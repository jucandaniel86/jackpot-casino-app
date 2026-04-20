<?php

	namespace App\Console\Commands;

	use App\Repositories\System\Jobs\Traits\LogsJobRuns;
	use Illuminate\Console\Command;


	class CryptoScanDeposits extends Command
	{
		use LogsJobRuns;

		protected $signature = 'crypto:scan-deposits {--currency=} {--shards=5}';
		protected $description = 'Dispatch shard jobs for deposit scanning';

		public function handle(): int
		{
			$currency = $this->option('currency');
			$shards = (int)$this->option('shards');

			$this->jobLogStart('SolanaDepositScan', [
				'currency' => $this->currency ?? null,
				'shard' => $this->shard ?? null,
			]);

			try {
				$count = 0;
				for ($i = 0; $i < $shards; $i++) {
					$count++;
					\App\Jobs\CryptoScanShardJob::dispatch($currency, $i, $shards);
					$this->jobLogSuccess(['new_deposits' => $count]);
				}
			} catch (\Throwable $e) {
				$this->jobLogFail($e);
				throw $e;
			}


			$this->info("Dispatched {$shards} shards for {$currency}");
			return 0;
		}
	}