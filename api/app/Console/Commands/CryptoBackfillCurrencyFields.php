<?php

	namespace App\Console\Commands;

	use App\Models\Transaction;
	use App\Models\Wallet;
	use Illuminate\Console\Command;

	class CryptoBackfillCurrencyFields extends Command
	{
		protected $signature = 'crypto:backfill-currency-fields {--chunk=500}';
		protected $description = 'Backfill currency_id/currency_code/network based on legacy wallets.currency';

		public function handle(): int
		{
			$chunk = (int)$this->option('chunk');

			Wallet::query()
				->whereNull('currency_id')
				->orWhereNull('currency_code')
				->orWhereNull('network')
				->chunkById($chunk, function ($rows) {
					foreach ($rows as $w) {
						[$network, $code] = $this->splitCurrency((string)$w->currency);
						$w->currency_id = (string)$w->currency; // legacy value becomes currency_id
						$w->currency_code = $code;
						$w->network = $network;
						$w->save();
					}
				});

			Transaction::query()
				->whereNull('currency_id')
				->orWhereNull('currency_code')
				->orWhereNull('network')
				->chunkById($chunk, function ($rows) {
					foreach ($rows as $t) {
						[$network, $code] = $this->splitCurrency((string)$t->currency);
						$t->currency_id = (string)$t->currency;
						$t->currency_code = $code;
						$t->network = $network;
						$t->save();
					}
				});

			$this->info('Backfill done.');
			return 0;
		}

		private function splitCurrency(string $currency): array
		{
			// ex: SOLANA:PEP or PEP
			if (str_contains($currency, ':')) {
				[$network, $code] = explode(':', $currency, 2);
				return [strtoupper($network), strtoupper($code)];
			}

			// fallback: treat as code only
			return [null, strtoupper($currency)];
		}
	}