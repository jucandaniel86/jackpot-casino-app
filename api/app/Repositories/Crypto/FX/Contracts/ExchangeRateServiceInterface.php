<?php

	namespace App\Repositories\Crypto\FX\Contracts;

	interface ExchangeRateServiceInterface
	{
		/**
		 * Returnează rate (fiat per 1 token), ex: EUR per 1 PEP.
		 */
		public function getRate(string $currencySymbol, string $fiat): array;
	}