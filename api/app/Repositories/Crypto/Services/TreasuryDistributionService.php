<?php

	namespace App\Repositories\Crypto\Services;

	use App\Repositories\Crypto\Contracts\TreasuryDistributionServiceInterface;
	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Repositories\Crypto\Support\Money;
	use App\Models\Wallet;
	use App\Models\WalletBalance;
	use Illuminate\Support\Facades\DB;

	class TreasuryDistributionService implements TreasuryDistributionServiceInterface
	{
		public function getDistribution(string $currencyId, int $scale = -1, ?string $intCasinoId = null): array
		{
			$currencyKey = CurrencyDecimals::normalizeCurrencyKey($currencyId);
			$symbol = str_contains($currencyKey, ':') ? explode(':', $currencyKey, 2)[1] : $currencyKey;

			$treasuryWalletId = (int)(config("crypto.treasury_wallet_ids.$symbol") ?? config("crypto.treasury_wallet_ids.$currencyId") ?? 0);
			if ($treasuryWalletId <= 0) {
				throw new \RuntimeException("Missing treasury wallet id for currency_id=$currencyId");
			}

			$treasuryWallet = Wallet::query()->findOrFail($treasuryWalletId);
			$decimals = CurrencyDecimals::internalForWallet($treasuryWallet);
			$displayScale = $scale >= 0 ? $scale : CurrencyDecimals::uiForCurrency($currencyKey);

			// Suma totală globală (user + treasury) pentru currency_id
			// total_base = available_base + reserved_base
			$totals = WalletBalance::query()
				->join('wallets', 'wallets.id', '=', 'wallet_balances.wallet_id')
				->where('wallets.currency', $currencyKey)
				->selectRaw('COALESCE(SUM(wallet_balances.available_base),0) as sum_available')
				->selectRaw('COALESCE(SUM(wallet_balances.reserved_base),0) as sum_reserved')
				->first();

			$totalBase = bcadd((string)$totals->sum_available, (string)$totals->sum_reserved, 0);

			// Treasury total_base
			$t = WalletBalance::query()->where('wallet_id', $treasuryWalletId)->first();
			$treasuryBase = '0';
			if ($t) {
				$treasuryBase = bcadd((string)$t->available_base, (string)$t->reserved_base, 0);
			}

			// Users global sau filtrat pe casino, în funcție de int_casino_id.
			$userBalancesQ = WalletBalance::query()
				->join('wallets', 'wallets.id', '=', 'wallet_balances.wallet_id')
				->where('wallets.currency', $currencyKey)
				->where('wallets.holder_type', 'App\\Models\\Player');

			if ($intCasinoId) {
				$userBalancesQ->join('players', 'players.id', '=', 'wallets.holder_id')
					->where('players.int_casino_id', '=', $intCasinoId);
			}

			$userTotals = $userBalancesQ
				->selectRaw('COALESCE(SUM(wallet_balances.available_base),0) as sum_available')
				->selectRaw('COALESCE(SUM(wallet_balances.reserved_base),0) as sum_reserved')
				->first();

			$usersBase = bcadd((string)$userTotals->sum_available, (string)$userTotals->sum_reserved, 0);

			$treasuryAmount = Money::baseToUi($treasuryBase, $decimals, $displayScale);
			$usersAmount = Money::baseToUi($usersBase, $decimals, $displayScale);
			$totalAmount = Money::baseToUi($totalBase, $decimals, $displayScale);

			// percente (cu 2 zecimale)
			$treasuryPct = $this->percent($treasuryBase, $totalBase, 2);
			$usersPct = $this->percent($usersBase, $totalBase, 2);

			return [
				[
					'label' => 'Casino wallet',
					'amount' => $treasuryAmount,
					'percent' => $treasuryPct, // string "20.00"
				],
				[
					'label' => 'User wallets',
					'amount' => $usersAmount,
					'percent' => $usersPct,
				],
			];
		}

		private function percent(string $partBase, string $totalBase, int $scale = 2): string
		{
			if (bccomp($totalBase, '0', 0) !== 1) {
				return number_format(0, $scale, '.', '');
			}

			// (part/total)*100 cu precizie mare, apoi format
			$ratio = bcdiv($partBase, $totalBase, 18);
			$pct = bcmul($ratio, '100', $scale);

			// bc* întoarce deja string cu scale fix
			return $pct;
		}
	}
