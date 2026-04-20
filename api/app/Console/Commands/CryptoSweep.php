<?php

	namespace App\Console\Commands;

	use App\Jobs\SweepSolanaJob;
	use App\Models\Player;
	use App\Repositories\System\Jobs\Traits\LogsJobRuns;
	use Illuminate\Console\Command;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Log;

	class CryptoSweep extends Command
	{
		use LogsJobRuns;

		protected $signature = 'crypto:sweep
			{--currency= : Currency id, ex SOLANA:PEP}
			{--limit=200 : Max wallets to dispatch}
			{--wallet-id= : Sweep a single wallet id}
			{--dry-run : Show wallets without dispatching}';

		protected $description = 'Dispatch sweep jobs for player wallets with available balance';

		public function handle(): int
		{
			$currency = $this->option('currency');
			$limit = (int)$this->option('limit');
			$walletId = $this->option('wallet-id');
			$dryRun = (bool)$this->option('dry-run');

			$this->jobLogStart('SolanaSweep', [
				'currency' => $currency,
				'limit' => $limit,
				'wallet_id' => $walletId,
				'dry_run' => $dryRun,
			]);

			try {
				Log::channel('crypto')->info('crypto sweep start', [
					'queue_default' => config('queue.default'),
					'currency' => $currency,
					'limit' => $limit,
					'wallet_id' => $walletId,
					'dry_run' => $dryRun,
				]);

				$q = DB::table('wallets as w')
					->join('wallet_balances as wb', 'wb.wallet_id', '=', 'w.id')
					->where('w.holder_type', Player::class)
					->where('wb.available_base', '>', 0);

				if ($walletId) {
					$q->where('w.id', (int)$walletId);
				}

				if (!empty($currency)) {
					$q->where('w.currency', $currency);
				}

				$walletIds = $q
					->orderByDesc('wb.available_base')
					->limit(max(1, $limit))
					->pluck('w.id')
					->all();

				if (!$walletIds) {
					Log::channel('crypto')->info('crypto sweep no wallets matched', [
						'currency' => $currency,
						'wallet_id' => $walletId,
					]);
					$this->info('No wallets to sweep.');
					$this->jobLogSuccess(['dispatched' => 0]);
					return 0;
				}

				Log::channel('crypto')->info('crypto sweep wallets matched', [
					'count' => count($walletIds),
					'wallet_ids' => $walletIds,
				]);

				$dispatched = 0;
				$errors = 0;
				foreach ($walletIds as $id) {
					if ($dryRun) {
						$this->line("DRY RUN wallet_id={$id}");
						continue;
					}

					try {
						SweepSolanaJob::dispatch((int)$id)->onQueue('crypto');
						$dispatched++;
					} catch (\Throwable $e) {
						$errors++;
						Log::channel('crypto')->error('crypto sweep dispatch failed', [
							'wallet_id' => (int)$id,
							'error' => $e->getMessage(),
						]);
						$this->warn("Failed dispatch wallet_id={$id}: {$e->getMessage()}");
					}
				}

				$this->info("Dispatched {$dispatched} sweep job(s). Errors: {$errors}.");
				Log::channel('crypto')->info('crypto sweep dispatched', [
					'count' => $dispatched,
					'errors' => $errors,
					'currency' => $currency,
					'wallet_id' => $walletId,
					'dry_run' => $dryRun,
				]);
				$this->jobLogSuccess(['dispatched' => $dispatched, 'errors' => $errors]);

				return 0;
			} catch (\Throwable $e) {
				$this->jobLogFail($e);
				$this->error($e->getMessage());
				throw $e;
			}
		}
	}
