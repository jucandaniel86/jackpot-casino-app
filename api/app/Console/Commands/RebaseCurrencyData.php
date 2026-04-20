<?php

namespace App\Console\Commands;

use App\Repositories\Crypto\Support\CurrencyDecimals;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebaseCurrencyData extends Command
{
	protected $signature = 'crypto:rebase-currency-data
		{--currency=SOLANA:PEP : Currency id (ex: SOLANA:PEP or PEP)}
		{--from-decimals= : Current stored decimals for legacy rows}
		{--to-decimals= : Target internal decimals}
		{--legacy-max-base= : Only rows with absolute base <= this are rebased}
		{--apply : Apply changes (without this flag it runs dry-run)}
		{--force-all : Allow rebasing all rows without legacy filter}';

	protected $description = 'Rebase legacy base-amount rows for a currency across core crypto/bonus tables.';

	public function handle(): int
	{
		$currencyInput = strtoupper(trim((string)$this->option('currency')));
		$currencyKey = CurrencyDecimals::normalizeCurrencyKey($currencyInput);
		$symbol = str_contains($currencyKey, ':') ? explode(':', $currencyKey, 2)[1] : $currencyKey;

		$from = (int)$this->option('from-decimals');
		$to = (int)$this->option('to-decimals');
		$apply = (bool)$this->option('apply');
		$forceAll = (bool)$this->option('force-all');
		$legacyMaxRaw = $this->option('legacy-max-base');
		$legacyMax = $legacyMaxRaw !== null ? trim((string)$legacyMaxRaw) : null;

		if ($from < 0 || $to < 0) {
			$this->error('--from-decimals and --to-decimals must be >= 0');
			return self::FAILURE;
		}

		$shift = $to - $from;
		if ($shift <= 0) {
			$this->error('Only positive rebase is supported (to-decimals must be greater than from-decimals).');
			return self::FAILURE;
		}

		if ($apply && !$forceAll && ($legacyMax === null || $legacyMax === '')) {
			$this->error('For safety, use --legacy-max-base when applying, or add --force-all.');
			return self::FAILURE;
		}

		$factor = '1' . str_repeat('0', $shift);

		$this->line('Mode: ' . ($apply ? 'APPLY' : 'DRY-RUN'));
		$this->line("Currency: {$currencyKey} ({$symbol})");
		$this->line("Rebase: from {$from} -> {$to} (x{$factor})");
		$this->line('Legacy filter: ' . (($legacyMax !== null && $legacyMax !== '') ? "<= {$legacyMax}" : 'none'));

		$walletIds = DB::table('wallets')
			->where('currency', $currencyKey)
			->pluck('id')
			->map(fn($id) => (int)$id)
			->all();

		$grantBaseQuery = DB::table('bonus_grants')
			->where(function ($q) use ($currencyKey, $symbol) {
				$q->where('currency_id', $currencyKey)
					->orWhere('currency_code', $symbol);
			});

		$legacyGrantIds = $this->legacyMaxFilter((clone $grantBaseQuery), ['amount_granted_base'], $legacyMax)
			->pluck('id')
			->map(fn($id) => (int)$id)
			->all();

		$grantIdsForEvents = ($legacyMax !== null && $legacyMax !== '')
			? $legacyGrantIds
			: (clone $grantBaseQuery)->pluck('id')->map(fn($id) => (int)$id)->all();

		$tables = [
			'wallet_balances' => $this->countForWalletBalances($walletIds, $legacyMax),
			'wallet_ledger_entries' => $this->countForCurrencyTable('wallet_ledger_entries', $walletIds, $currencyKey, ['amount_base'], $legacyMax),
			'transaction' => $this->countForCurrencyTable('transaction', $walletIds, $currencyKey, ['amount_base'], $legacyMax),
			'withdraw_requests' => $this->countForCurrencyTable('withdraw_requests', $walletIds, $currencyKey, ['amount_base'], $legacyMax),
			'deposits' => $this->countForCurrencyTable('deposits', $walletIds, $currencyKey, ['amount_base'], $legacyMax),
			'bonus_grants' => count($grantIdsForEvents),
			'bonus_grant_events' => empty($grantIdsForEvents)
				? 0
				: (int)DB::table('bonus_grant_events')->whereIn('bonus_grant_id', $grantIdsForEvents)->count(),
		];

		foreach ($tables as $table => $count) {
			$this->line("Rows matched {$table}: {$count}");
		}

		if (!$apply) {
			$this->warn('Dry-run complete. Re-run with --apply to persist.');
			return self::SUCCESS;
		}

		DB::transaction(function () use ($walletIds, $currencyKey, $legacyMax, $factor, $to, $grantIdsForEvents) {
			// wallet_balances
			$q = DB::table('wallet_balances')->whereIn('wallet_id', $walletIds);
			$q = $this->legacyMaxFilter($q, ['available_base', 'reserved_base'], $legacyMax);
			$q->update([
				'available_base' => DB::raw("available_base * {$factor}"),
				'reserved_base' => DB::raw("reserved_base * {$factor}"),
				'updated_at' => now(),
			]);

			// wallet_ledger_entries
			$q = $this->currencyTableQuery('wallet_ledger_entries', $walletIds, $currencyKey);
			$q = $this->legacyMaxFilter($q, ['amount_base'], $legacyMax);
			$q->update([
				'amount_base' => DB::raw("amount_base * {$factor}"),
				'decimals' => $to,
				'updated_at' => now(),
			]);

			// transaction
			$q = $this->currencyTableQuery('transaction', $walletIds, $currencyKey);
			$q = $this->legacyMaxFilter($q, ['amount_base'], $legacyMax);
			$q->update([
				'amount_base' => DB::raw("amount_base * {$factor}"),
				'decimals' => $to,
				'updated_at' => now(),
			]);

			// withdraw_requests
			$q = $this->currencyTableQuery('withdraw_requests', $walletIds, $currencyKey);
			$q = $this->legacyMaxFilter($q, ['amount_base'], $legacyMax);
			$q->update([
				'amount_base' => DB::raw("amount_base * {$factor}"),
				'decimals' => $to,
				'updated_at' => now(),
			]);

			// deposits
			$q = $this->currencyTableQuery('deposits', $walletIds, $currencyKey);
			$q = $this->legacyMaxFilter($q, ['amount_base'], $legacyMax);
			$q->update([
				'amount_base' => DB::raw("amount_base * {$factor}"),
				'decimals' => $to,
				'updated_at' => now(),
			]);

			// bonus_grants + events
			if (!empty($grantIdsForEvents)) {
				DB::table('bonus_grants')
					->whereIn('id', $grantIdsForEvents)
					->update([
						'amount_granted_base' => DB::raw("amount_granted_base * {$factor}"),
						'amount_remaining_base' => DB::raw("amount_remaining_base * {$factor}"),
						'wagering_required_base' => DB::raw("wagering_required_base * {$factor}"),
						'wagering_progress_base' => DB::raw("wagering_progress_base * {$factor}"),
						'updated_at' => now(),
					]);

				DB::table('bonus_grant_events')
					->whereIn('bonus_grant_id', $grantIdsForEvents)
					->update([
						'amount_base' => DB::raw("amount_base * {$factor}"),
						'updated_at' => now(),
					]);
			}
		});

		$this->info('Rebase applied successfully.');
		return self::SUCCESS;
	}

	private function currencyTableQuery(string $table, array $walletIds, string $currencyKey)
	{
		return DB::table($table)
			->where(function ($q) use ($walletIds, $currencyKey) {
				if (!empty($walletIds)) {
					$q->whereIn('wallet_id', $walletIds)
						->orWhere('currency', $currencyKey);
					return;
				}
				$q->where('currency', $currencyKey);
			});
	}

	private function countForCurrencyTable(string $table, array $walletIds, string $currencyKey, array $amountColumns, ?string $legacyMax): int
	{
		$q = $this->currencyTableQuery($table, $walletIds, $currencyKey);
		$q = $this->legacyMaxFilter($q, $amountColumns, $legacyMax);
		return (int)$q->count();
	}

	private function countForWalletBalances(array $walletIds, ?string $legacyMax): int
	{
		if (empty($walletIds)) {
			return 0;
		}
		$q = DB::table('wallet_balances')->whereIn('wallet_id', $walletIds);
		$q = $this->legacyMaxFilter($q, ['available_base', 'reserved_base'], $legacyMax);
		return (int)$q->count();
	}

	private function legacyMaxFilter($query, array $columns, ?string $legacyMax)
	{
		if ($legacyMax === null || $legacyMax === '') {
			return $query;
		}

		return $query->where(function ($q) use ($columns, $legacyMax) {
			foreach ($columns as $idx => $col) {
				if ($idx === 0) {
					$q->whereRaw("ABS({$col}) <= ?", [$legacyMax]);
					continue;
				}
				$q->orWhereRaw("ABS({$col}) <= ?", [$legacyMax]);
			}
		});
	}
}
