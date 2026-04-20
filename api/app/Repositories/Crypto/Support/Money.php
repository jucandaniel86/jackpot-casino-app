<?php

	namespace App\Repositories\Crypto\Support;

	/**
	 * Utility pentru conversii money-safe:
	 * - base units (string exact) <-> UI decimal (string)
	 * - fără float, doar string math
	 */
	class Money
	{
		/**
		 * Convertește base units (ex: "123456789") la UI string (ex: "123.456789")
		 * Fără rotunjire (taie dacă e nevoie).
		 */
		public static function baseToUi(string $base, int $decimals, int $uiDecimals = 8): string
		{
			$uiDecimals = max(0, $uiDecimals);
			$base = ltrim($base, '+');
			$neg = false;
			if (str_starts_with($base, '-')) {
				$neg = true;
				$base = substr($base, 1);
			}

			// normalize
			$base = ltrim($base, '0');
			if ($base === '') $base = '0';

			if ($decimals === 0) {
				$ui = $base;
			} else {
				if (strlen($base) <= $decimals) {
					$base = str_pad($base, $decimals + 1, '0', STR_PAD_LEFT);
				}

				$intPart = substr($base, 0, -$decimals);
				$frac = substr($base, -$decimals);

				// normalizăm la uiDecimals (default 8)
				$frac = str_pad($frac, $uiDecimals, '0');
				if (strlen($frac) > $uiDecimals) {
					$frac = substr($frac, 0, $uiDecimals);
				}

				$ui = $uiDecimals === 0 ? $intPart : ($intPart . '.' . $frac);
			}

			return $neg ? '-' . $ui : $ui;
		}

		/**
		 * Convertește UI string (ex: "1.2345") la base units (string exact).
		 * NU rotunjește, ci taie la decimals.
		 */
		public static function uiToBase(string $ui, int $decimals): string
		{
			$ui = trim($ui);
			if ($ui === '') return '0';

			$neg = false;
			if (str_starts_with($ui, '-')) {
				$neg = true;
				$ui = substr($ui, 1);
			}

			if (!str_contains($ui, '.')) {
				$int = $ui;
				$frac = '';
			} else {
				[$int, $frac] = explode('.', $ui, 2);
			}

			$int = ltrim($int, '0');
			if ($int === '') $int = '0';

			// normalize frac
			$frac = preg_replace('/[^0-9]/', '', $frac);
			$frac = str_pad($frac, $decimals, '0');
			if (strlen($frac) > $decimals) {
				$frac = substr($frac, 0, $decimals);
			}

			$base = $int . $frac;
			$base = ltrim($base, '0');
			if ($base === '') $base = '0';

			return $neg ? '-' . $base : $base;
		}

		/**
		 * Compara două sume în base units.
		 * Returnează: -1, 0, 1 (ca bccomp)
		 */
		public static function cmp(string $a, string $b): int
		{
			return bccomp($a, $b, 0);
		}

		/**
		 * Adunare în base units (string).
		 */
		public static function add(string $a, string $b): string
		{
			return bcadd($a, $b, 0);
		}

		/**
		 * Scădere în base units (string).
		 */
		public static function sub(string $a, string $b): string
		{
			return bcsub($a, $b, 0);
		}

		/**
		 * Rebase integer base units between different decimals scales.
		 * Example: amount=100000000, from=6, to=9 => 100000000000
		 */
		public static function rebaseBase(string $amountBase, int $fromDecimals, int $toDecimals): string
		{
			$amountBase = ltrim(trim($amountBase), '+');
			if ($amountBase === '' || $amountBase === '-') {
				return '0';
			}

			if ($fromDecimals === $toDecimals) {
				return $amountBase;
			}

			if ($toDecimals > $fromDecimals) {
				$pow = bcpow('10', (string)($toDecimals - $fromDecimals), 0);
				return bcmul($amountBase, $pow, 0);
			}

			$pow = bcpow('10', (string)($fromDecimals - $toDecimals), 0);
			return bcdiv($amountBase, $pow, 0);
		}
	}
