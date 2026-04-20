<?php

	namespace App\Repositories\Crypto\FX\Services;

	use App\Repositories\Crypto\FX\Contracts\ExchangeRateServiceInterface;
	use Illuminate\Support\Facades\Cache;
	use Illuminate\Support\Facades\Http;

	class ExchangeRateService implements ExchangeRateServiceInterface
	{
		public function getRate(string $currencySymbol, string $fiat): array
		{
			$currencySymbol = strtoupper($currencySymbol); // PEP
			$fiat = strtoupper($fiat); // EUR

			$cacheKey = "fx:rate:{$currencySymbol}:{$fiat}";
			$ttl = (int)config('crypto.fx.cache_ttl_seconds', 60);

			return Cache::remember($cacheKey, $ttl, function () use ($currencySymbol, $fiat) {
				$currencyCode = "SOLANA:{$currencySymbol}";
				$cfg = config("crypto.currencies.{$currencyCode}");
				if (!$cfg || empty($cfg['mint']) || ($cfg['chain'] ?? null) !== 'solana') {
					throw new \RuntimeException("FX config missing for {$currencyCode}");
				}

				$mint = $cfg['mint'];
				$provider = config('crypto.fx.provider', 'auto');

				// 1) CoinGecko (dacă e auto sau coingecko)
				if (in_array($provider, ['auto', 'coingecko'], true)) {
					$rate = $this->tryCoinGeckoSolanaMintToFiat($mint, $fiat);
					if ($rate !== null) {
						return $this->buildResult($currencySymbol, $fiat, (float)$rate, 'coingecko', $mint);
					}
					if ($provider === 'coingecko') {
						throw new \RuntimeException("FX rate not found for {$currencySymbol}/{$fiat}");
					}
				}

				// 2) DexScreener fallback (auto sau dexscreener)
				if (in_array($provider, ['auto', 'dexscreener'], true)) {
					$priceUsd = $this->getDexScreenerPriceUsdForToken('solana', $mint);
					if ($priceUsd === null) {
						if ($provider === 'dexscreener') {
							throw new \RuntimeException("FX rate not found for {$currencySymbol}/{$fiat}");
						}
					} else {
						$usdToFiat = $this->getUsdToFiatRate($fiat); // USD->EUR
						$rateFiat = $priceUsd * $usdToFiat;
						return $this->buildResult($currencySymbol, $fiat, (float)$rateFiat, 'dexscreener+usd_fx', $mint, [
							'price_usd' => $priceUsd,
							'usd_to_fiat' => $usdToFiat,
						]);
					}
				}

				// 3) Manual fallback (dacă vrei mai târziu)
				throw new \RuntimeException("FX rate not found for {$currencySymbol}/{$fiat}");
			});
		}

		private function buildResult(string $sym, string $fiat, float $rate, string $provider, string $mint, array $meta = []): array
		{
			return [
				'pair' => "{$sym}/{$fiat}",
				'rate' => $rate,              // fiat per 1 token
				'provider' => $provider,
				'mint' => $mint,
				'meta' => $meta,
				'fetched_at' => now()->toISOString(),
			];
		}

		private function tryCoinGeckoSolanaMintToFiat(string $mint, string $fiat): ?float
		{
			$base = rtrim(config('crypto.fx.coingecko.base_url'), '/');
			$apiKey = config('crypto.fx.coingecko.api_key');

			$url = "{$base}/simple/token_price/solana";
			$resp = Http::timeout(10)
				->when($apiKey, fn($h) => $h->withHeader('x-cg-pro-api-key', $apiKey))
				->get($url, [
					'contract_addresses' => $mint,
					'vs_currencies' => strtolower($fiat),
				]);

			if (!$resp->ok()) return null;

			$json = $resp->json();
			$val = $json[strtolower($mint)][strtolower($fiat)] ?? null;

			return $val !== null ? (float)$val : null;
		}

		/**
		 * Returnează priceUsd (float) din cel mai “relevant” pool (max liquidity).
		 * DexScreener: /token-pairs/v1/{chainId}/{tokenAddress}
		 */
		private function getDexScreenerPriceUsdForToken(string $chainId, string $tokenAddress): ?float
		{
			$base = rtrim(config('crypto.fx.dexscreener.base_url'), '/');
			$url = "{$base}/token-pairs/v1/{$chainId}/{$tokenAddress}";

			$resp = Http::timeout(10)->get($url);
			if (!$resp->ok()) return null;

			$pairs = $resp->json();
			if (!is_array($pairs) || count($pairs) === 0) return null;

			// alege pair-ul cu liquidity.max (dacă lipsește, alege primul)
			$best = null;
			$bestLiq = -1;

			foreach ($pairs as $p) {
				$liq = (float)($p['liquidity']['usd'] ?? 0);
				if ($liq > $bestLiq) {
					$bestLiq = $liq;
					$best = $p;
				}
			}

			$priceUsd = $best['priceUsd'] ?? null;
			if ($priceUsd === null) return null;

			return (float)$priceUsd;
		}

		/**
		 * USD -> FIAT (ex EUR) din open.er-api.com, cache 1h by default.
		 */
		private function getUsdToFiatRate(string $fiat): float
		{
			$fiat = strtoupper($fiat);
			if ($fiat === 'USD') return 1.0;

			$cacheKey = "fx:usd_to:{$fiat}";
			$ttl = (int)config('crypto.fx.fx_rates.cache_ttl_seconds', 3600);

			return Cache::remember($cacheKey, $ttl, function () use ($fiat) {
				$base = rtrim(config('crypto.fx.fx_rates.base_url'), '/');
				$url = "{$base}/v6/latest/USD";

				$resp = Http::timeout(10)->get($url);
				if (!$resp->ok()) {
					throw new \RuntimeException("FX rates provider error: HTTP {$resp->status()}");
				}

				$json = $resp->json();

				// Unele payload-uri folosesc "rates", altele "conversion_rates".
				$rates = $json['rates'] ?? $json['conversion_rates'] ?? null;
				if (!is_array($rates) || !isset($rates[$fiat])) {
					throw new \RuntimeException("USD->{$fiat} rate missing");
				}

				return (float)$rates[$fiat];
			});
		}
	}