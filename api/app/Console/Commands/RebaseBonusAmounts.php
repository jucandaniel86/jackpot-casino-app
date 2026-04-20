<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebaseBonusAmounts extends Command
{
	protected $signature = 'bonus:rebase-amounts
		{--currency=SOLANA:PEP : Currency id (ex: SOLANA:PEP)}
		{--from-decimals= : Existing stored base scale}
		{--to-decimals= : Target base scale}
		{--apply : Apply updates (without this flag runs dry-run)}
		{--chunk=500 : Chunk size for updates}';

	protected $description = 'Rebase historical bonus amounts between different internal decimal scales.';

	public function handle(): int
	{
		$currency = strtoupper(trim((string)$this->option('currency')));
		$from = (int)$this->option('from-decimals');
		$to = (int)$this->option('to-decimals');
		$apply = (bool)$this->option('apply');
		$chunk = max(50, min(5000, (int)$this->option('chunk')));

		if ($currency === '') {
			$this->error('Missing --currency');
			return self::FAILURE;
		}
		if ($from < 0 || $to < 0) {
			$this->error('--from-decimals and --to-decimals must be >= 0');
			return self::FAILURE;
		}

		$shift = $to - $from;
		$symbol = str_contains($currency, ':') ? explode(':', $currency, 2)[1] : $currency;
		if ($shift === 0) {
			$this->warn('No-op: from-decimals equals to-decimals.');
			return self::SUCCESS;
		}

		$this->line('Mode: ' . ($apply ? 'APPLY' : 'DRY-RUN'));
		$this->line("Currency: {$currency}");
		$this->line("Rebase: 10^({$to} - {$from}) = 10^{$shift}");

		$grantIds = DB::table('bonus_grants')
			->where(function ($q) use ($currency, $symbol) {
				$q->where('currency_id', $currency)
					->orWhere('currency_code', $symbol);
			})
			->pluck('id')
			->map(fn($id) => (int)$id)
			->all();

		$grantCount = count($grantIds);
		if ($grantCount === 0) {
			$this->warn('No bonus_grants found for this currency.');
			return self::SUCCESS;
		}

		$eventCount = (int)DB::table('bonus_grant_events')->whereIn('bonus_grant_id', $grantIds)->count();
		$this->line("Rows to process: grants={$grantCount}, events={$eventCount}");

		$preview = DB::table('bonus_grants')
			->whereIn('id', array_slice($grantIds, 0, 5))
			->get(['id', 'amount_granted_base', 'amount_remaining_base', 'wagering_required_base', 'wagering_progress_base']);

		foreach ($preview as $row) {
			$newGranted = $this->scaleInt((string)$row->amount_granted_base, $shift);
			$this->line("sample grant #{$row->id}: {$row->amount_granted_base} -> {$newGranted}");
		}

		if (!$apply) {
			$this->warn('Dry-run only. Re-run with --apply to persist changes.');
			return self::SUCCESS;
		}

		$updatedGrants = 0;
		$updatedEvents = 0;
		$errors = 0;

		foreach (array_chunk($grantIds, $chunk) as $idsChunk) {
			DB::transaction(function () use ($idsChunk, $shift, &$updatedGrants, &$updatedEvents, &$errors) {
				$grants = DB::table('bonus_grants')
					->whereIn('id', $idsChunk)
					->lockForUpdate()
					->get();

				foreach ($grants as $g) {
					try {
						DB::table('bonus_grants')
							->where('id', $g->id)
							->update([
								'amount_granted_base' => $this->scaleInt((string)$g->amount_granted_base, $shift),
								'amount_remaining_base' => $this->scaleInt((string)$g->amount_remaining_base, $shift),
								'wagering_required_base' => $this->scaleInt((string)$g->wagering_required_base, $shift),
								'wagering_progress_base' => $this->scaleInt((string)$g->wagering_progress_base, $shift),
								'updated_at' => now(),
							]);
						$updatedGrants++;
					} catch (\Throwable $e) {
						$errors++;
						$this->error("grant#{$g->id} skipped: {$e->getMessage()}");
					}
				}

				$events = DB::table('bonus_grant_events')
					->whereIn('bonus_grant_id', $idsChunk)
					->lockForUpdate()
					->get();

				foreach ($events as $e) {
					try {
						DB::table('bonus_grant_events')
							->where('id', $e->id)
							->update([
								'amount_base' => $this->scaleInt((string)$e->amount_base, $shift),
								'updated_at' => now(),
							]);
						$updatedEvents++;
					} catch (\Throwable $ex) {
						$errors++;
						$this->error("event#{$e->id} skipped: {$ex->getMessage()}");
					}
				}
			});
		}

		$this->info("Done. updated grants={$updatedGrants}, events={$updatedEvents}, errors={$errors}");
		if ($errors > 0) {
			$this->warn('Some rows were skipped; check console output.');
		}

		return self::SUCCESS;
	}

	private function scaleInt(string $value, int $shift): string
	{
		$value = trim($value);
		if ($value === '') {
			return '0';
		}

		$negative = false;
		if (str_starts_with($value, '-')) {
			$negative = true;
			$value = substr($value, 1);
		}

		$value = ltrim($value, '0');
		if ($value === '') {
			$value = '0';
		}

		if ($shift > 0) {
			$out = $value . str_repeat('0', $shift);
			return $negative ? "-{$out}" : $out;
		}

		$drop = abs($shift);
		if ($drop === 0) {
			return $negative ? "-{$value}" : $value;
		}

		if ($value === '0') {
			return '0';
		}

		if (strlen($value) < $drop) {
			throw new \RuntimeException("cannot divide {$value} by 10^{$drop} exactly");
		}

		$tail = substr($value, -$drop);
		if ((int)$tail !== 0) {
			throw new \RuntimeException("non-divisible value {$value} for shift {$shift}");
		}

		$out = substr($value, 0, -$drop);
		$out = ltrim((string)$out, '0');
		if ($out === '') {
			$out = '0';
		}

		return $negative ? "-{$out}" : $out;
	}
}
