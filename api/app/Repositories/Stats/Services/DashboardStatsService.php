<?php

	namespace App\Repositories\Stats\Services;

	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Repositories\Crypto\Support\Money;
	use App\Repositories\Stats\Contracts\DashboardStatsServiceInterface;
	use App\Models\Bet;
	use App\Models\Transaction;
	use Carbon\Carbon;

	class DashboardStatsService implements DashboardStatsServiceInterface
	{
		public function today(array $filters = []): array
		{
			$currencyId = $filters['currency_id'] ?? null;
			$currencyCode = $filters['currency_code'] ?? null;
			$casinoID = $filters['int_casino_id'] ?? config('casino.defaultCasinoId');
			$currencyRef = (string)($currencyId ?: $currencyCode ?: config('crypto.defaultCurrency', 'SOLANA:PEP'));

			$start = (isset($filters['from'])) ? Carbon::parse($filters['from']) : Carbon::today();
			$end = (isset($filters['to'])) ? Carbon::parse($filters['to']) : Carbon::tomorrow();

			// --- BETS aggregation (GGR, active players, biggest win)
			$betsQ = Bet::query()
				->where('when_placed', '>=', $start)
				->where('when_placed', '<', $end)
				->where('bets.int_casino_id', $casinoID);

			// Atenție: în schema ta bets.currency e string(3) (ex: "PEP").
			// Dacă vrei filtrare pe currencyId "SOLANA:PEP", trebuie mapare -> "PEP".
			// Provizoriu: acceptă direct currency_code în filters.
			$betCurrency = $currencyCode ?: $this->symbolFromCurrency($currencyRef);
			if ($betCurrency) {
				$betsQ->where('currency', strtoupper($betCurrency));
			}

			$totalStake = (clone $betsQ)
				->where('transaction_type', 'bet')
				->sum('stake');

			$totalPayout = (clone $betsQ)
				->where('transaction_type', 'win')
				->sum('payout');

			// Refund: în codul tău refund-row are payout = stake (credit back).
			$totalRefund = (clone $betsQ)
				->where('transaction_type', 'refund')
				->sum('payout');

			$uiDecimals = CurrencyDecimals::uiForCurrency($currencyRef);
			$ggr = $this->formatCurrencyValue(
				bcsub(bcsub((string)$totalStake, (string)$totalPayout, 8), (string)$totalRefund, 8),
				$uiDecimals
			);

			$activePlayers = (clone $betsQ)
				->where('transaction_type', 'bet')
				->distinct('user_id')
				->count('user_id');

			$biggestWinRow = (clone $betsQ)
				->where('bets.transaction_type', 'win')
				->join('players', 'players.id', '=', 'bets.user_id')
				->join('games', 'games.game_id', '=', 'bets.game_id')
				->orderByDesc('bets.payout')
				->select([
					'players.username as username',
					'games.name as game_name',
					'bets.payout',
					'bets.when_placed',
					'bets.operator_transaction_id',
					'bets.operator_round_id',
				])
				->first();

			$biggestWin = $biggestWinRow ? [
				'username' => $biggestWinRow->username,
				'game' => $biggestWinRow->game_name,
				'amount' => $this->formatCurrencyValue((string)$biggestWinRow->payout, $uiDecimals),
				'round_id' => $biggestWinRow->operator_round_id,
				'tx_id' => $biggestWinRow->operator_transaction_id,
				'at' => \Carbon\Carbon::parse($biggestWinRow->when_placed)->toISOString(),
			] : null;

			// --- Deposits / Withdrawals from transactions
			$txQ = Transaction::query()
				->where('created_at', '>=', $start)
				->where('created_at', '<', $end)
				->whereIn('type', ['deposit', 'withdraw'])
				->where('int_casino_id', $casinoID)
				->where('status', 'confirmed');

			$txCurrency = $this->dbCurrency($currencyRef);
			if ($txCurrency !== '') {
				$txQ->where('currency', $txCurrency);
			}

			$decimals = CurrencyDecimals::internalForCurrency($currencyRef);

			$depositsToday = (clone $txQ)->where('type', 'deposit')->sum('amount_base'); // sau amount_base
			$withdrawalsToday = (clone $txQ)->where('type', 'withdraw')->sum('amount_base');

			$depositCount = (clone $txQ)->where('type', 'deposit')->count();
			$withdrawCount = (clone $txQ)->where('type', 'withdraw')->count();

			return [
				'range' => [
					'from' => $start->toISOString(),
					'to' => $end->toISOString(),
				],
				'kpi' => [
					'ggr_today' => $ggr,
					'active_players_today' => $activePlayers,
					'deposits_today' => Money::baseToUi($depositsToday, $decimals, $uiDecimals),
					'withdrawals_today' => Money::baseToUi($withdrawalsToday, $decimals, $uiDecimals),
					'deposit_count_today' => $depositCount,
					'withdraw_count_today' => $withdrawCount,
					'biggest_win_today' => $biggestWin,
				],
				'currency' => $txCurrency !== '' ? $txCurrency : $currencyRef
			];
		}

		private function symbolFromCurrency(string $currency): ?string
		{
			$currency = strtoupper(trim($currency));
			if ($currency === '') {
				return null;
			}

			if (str_contains($currency, ':')) {
				return explode(':', $currency, 2)[1] ?: null;
			}

			return $currency;
		}

		private function dbCurrency(string $currency): string
		{
			$currency = strtoupper(trim($currency));
			if ($currency === '') {
				return '';
			}

			return str_contains($currency, ':') ? $currency : "SOLANA:{$currency}";
		}

		private function formatCurrencyValue(string $value, int $uiDecimals): string
		{
			$value = trim($value);
			if ($value === '') {
				return '0';
			}

			return bcadd($value, '0', max(0, $uiDecimals));
		}
	}
