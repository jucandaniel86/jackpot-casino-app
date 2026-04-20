<?php

namespace App\Repositories\Crypto\Support;

use App\Models\Wallet;

class CurrencyDecimals
{
	public static function internalForWallet(Wallet $wallet): int
	{
		return self::internalForCurrency(self::walletCurrencyKey($wallet));
	}

	public static function uiForWallet(Wallet $wallet): int
	{
		return self::uiForCurrency(self::walletCurrencyKey($wallet));
	}

	public static function internalForCurrency(string $currency): int
	{
		$key = self::normalizeCurrencyKey($currency);
		$d = (int)config("crypto.currencies.{$key}.internal_decimals", 0);
		if ($d > 0) {
			return $d;
		}

		$legacy = (int)config("crypto.currencies.{$key}.decimals", 0);
		if ($legacy > 0) {
			return $legacy;
		}

		return 9;
	}

	public static function uiForCurrency(string $currency): int
	{
		$key = self::normalizeCurrencyKey($currency);
		$d = (int)config("crypto.currencies.{$key}.ui_decimals", -1);
		if ($d >= 0) {
			return $d;
		}

		return self::internalForCurrency($key);
	}

	public static function normalizeCurrencyKey(string $currency): string
	{
		$currency = strtoupper(trim($currency));
		if ($currency === '') {
			return $currency;
		}

		if (str_contains($currency, ':')) {
			return $currency;
		}

		// Keep compatibility for places where only symbol is passed (ex: PEP).
		if (config("crypto.currencies.{$currency}") !== null) {
			return $currency;
		}

		$solanaKey = "SOLANA:{$currency}";
		if (config("crypto.currencies.{$solanaKey}") !== null) {
			return $solanaKey;
		}

		return $currency;
	}

	private static function walletCurrencyKey(Wallet $wallet): string
	{
		$currency = (string)($wallet->currency ?: $wallet->currency_id ?: '');
		return self::normalizeCurrencyKey($currency);
	}
}
