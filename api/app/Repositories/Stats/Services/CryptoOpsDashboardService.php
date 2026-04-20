<?php

	namespace App\Repositories\Stats\Services;

	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Repositories\Crypto\Support\Money;
	use App\Repositories\Stats\Contracts\CryptoOpsDashboardServiceInterface;
	use Carbon\Carbon;
	use Illuminate\Support\Facades\DB;

	class CryptoOpsDashboardService implements CryptoOpsDashboardServiceInterface
	{
		public function sweepsReport(array $filters): array
		{
			$from = Carbon::parse($filters['from']);
			$to = Carbon::parse($filters['to']);

			$currencyInput = strtoupper($filters['currency_code'] ?? 'PEP');
			$currencyDb = $this->dbCurrency($currencyInput);
			$casinoID = $filters['int_casino_id'] ?? null;

			$decimals = CurrencyDecimals::internalForCurrency($currencyDb);
			$scaleUi = CurrencyDecimals::uiForCurrency($currencyDb);

			$baseQ = DB::table('transaction as t')
				->where('t.type', 'sweep')
				->where('t.created_at', '>=', $from)
				->where('t.created_at', '<', $to)
				->when($currencyDb, fn($q) => $q->where('t.currency', $currencyDb))
				->when($casinoID, fn($q) => $q->where('t.int_casino_id', $casinoID));

			// counts
			$counts = (clone $baseQ)
				->selectRaw("
                SUM(CASE WHEN t.status='pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN t.status='failed' THEN 1 ELSE 0 END) as failed_count
            ")->first();

			// sums base
			$sums = (clone $baseQ)
				->selectRaw("
                COALESCE(SUM(CASE WHEN t.status='pending' THEN t.amount_base ELSE 0 END),0) as pending_sum_base,
                COALESCE(SUM(CASE WHEN t.status='failed' THEN t.amount_base ELSE 0 END),0) as failed_sum_base
            ")->first();

			$pendingBase = (string)($sums->pending_sum_base ?? '0');
			$failedBase = (string)($sums->failed_sum_base ?? '0');

			return [
				'range' => [
					'from' => $from->toISOString(),
					'to' => $to->toISOString(),
				],
				'filters' => [
					'currency_code' => $currencyDb,
					'currency_symbol' => $this->symbolFromDbCurrency($currencyDb),
					'decimals' => $decimals,
				],
				'metrics' => [
					'pending' => [
						'count' => (int)($counts->pending_count ?? 0),
						'amount_base' => $pendingBase,
						'amount_ui' => Money::baseToUi($pendingBase, $decimals, $scaleUi),
					],
					'failed' => [
						'count' => (int)($counts->failed_count ?? 0),
						'amount_base' => $failedBase,
						'amount_ui' => Money::baseToUi($failedBase, $decimals, $scaleUi),
					],
				],
			];
		}

		private function dbCurrency(string $currencyInput): string
		{
			$currencyInput = strtoupper($currencyInput);
			if (str_contains($currencyInput, ':')) return $currencyInput;
			return "SOLANA:{$currencyInput}";
		}

		private function symbolFromDbCurrency(string $dbCurrency): string
		{
			return str_contains($dbCurrency, ':') ? explode(':', $dbCurrency, 2)[1] : $dbCurrency;
		}

	}
