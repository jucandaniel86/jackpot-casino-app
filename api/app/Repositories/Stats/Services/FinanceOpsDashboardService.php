<?php

	namespace App\Repositories\Stats\Services;

	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Repositories\Crypto\Support\Money;
	use App\Repositories\Stats\Contracts\FinanceOpsDashboardServiceInterface;
	use Carbon\Carbon;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Log;

	class FinanceOpsDashboardService implements FinanceOpsDashboardServiceInterface
	{
		// Ajustează dacă morph class e altul (ex: App\Models\Player vs App\Models\Player)
		private const PLAYER_HOLDER_TYPE = 'App\\Models\\Player';

		public function report(array $filters): array
		{
			$from = Carbon::parse($filters['from']);
			$to = Carbon::parse($filters['to']);

			$currencyInput = strtoupper($filters['currency_code'] ?? 'PEP');
			$currency = $this->dbCurrency($currencyInput);
			$casinoId = $filters['int_casino_id'] ?? null;

			$unclaimedDays = (int)($filters['unclaimed_days'] ?? 7);
			$unclaimedDays = max(1, min($unclaimedDays, 365));

			$symbol = str_contains($currency, ':') ? explode(':', $currency, 2)[1] : $currency;
			$decimals = CurrencyDecimals::internalForCurrency($currency);
			$scaleUi = CurrencyDecimals::uiForCurrency($currency);

			/**
			 * 11) Deposits & Withdrawals volume
			 * 12) Net cashflow
			 * 13) Average deposit size
			 *
			 * Adevărul = amount_base (integer base units).
			 * Amount UI îl construim din base + decimals.
			 */
			$txAgg = $this->txAgg($from, $to, $currency, $casinoId);

			$depositCount = (int)($txAgg['deposit']['count'] ?? 0);
			$withdrawCount = (int)($txAgg['withdraw']['count'] ?? 0);

			$depositBase = (string)($txAgg['deposit']['sum_base'] ?? '0');
			$withdrawBase = (string)($txAgg['withdraw']['sum_base'] ?? '0');

			$depositUi = $this->baseToUi($depositBase, $decimals, $scaleUi);
			$withdrawUi = $this->baseToUi($withdrawBase, $decimals, $scaleUi);

			$netBase = $this->bcsub0($depositBase, $withdrawBase);
			$netUi = $this->baseToUi($netBase, $decimals, $scaleUi);

			$avgDepositBase = $depositCount > 0
				? $this->bcdiv0($depositBase, (string)$depositCount)
				: '0';
			$avgDepositUi = $this->baseToUi($avgDepositBase, $decimals, $scaleUi);

			/**
			 * 14) Wallet balances overview
			 * source of truth = wallet_balances
			 */
			$userTotals = $this->walletBalancesTotals(
				holderType: self::PLAYER_HOLDER_TYPE,
				currency: $currency,
				casinoId: $casinoId
			);

			$nonUserTotals = $this->walletBalancesTotals(
				holderType: null, // != player
				currency: $currency
			);

			// Convert totals to UI
			$userAvailUi = $this->baseToUi($userTotals['available_base'], $decimals, $scaleUi);
			$userResUi = $this->baseToUi($userTotals['reserved_base'], $decimals, $scaleUi);
			$userTotalUi = $this->baseToUi($userTotals['total_base'], $decimals, $scaleUi);

			$nonAvailUi = $this->baseToUi($nonUserTotals['available_base'], $decimals, $scaleUi);
			$nonResUi = $this->baseToUi($nonUserTotals['reserved_base'], $decimals, $scaleUi);
			$nonTotalUi = $this->baseToUi($nonUserTotals['total_base'], $decimals, $scaleUi);

			/**
			 * 15) Unclaimed balances
			 * A) never_played: deposited ever, 0 bets ever
			 * B) inactive: deposited ever, no bet in last N days
			 *
			 * Important: unclaimed balances = sum(wallet_balances) pentru walleturile userilor (currency).
			 */
			$neverPlayed = $this->calcUnclaimedNeverPlayed(currency: $currency, casinoId: $casinoId, decimals: $decimals, scaleUi: $scaleUi);
			$inactive = $this->calcUnclaimedInactive(currency: $currency, casinoId: $casinoId, days: $unclaimedDays, decimals: $decimals, scaleUi: $scaleUi);
			$debug = [];

//
//			$debug = [
//				'withdraw_counts_by_status' => DB::table('transaction')
//					->where('type', 'withdraw')
//					->where('currency', $currency)
//					->selectRaw('status, COUNT(*) c')
//					->groupBy('status')
//					->pluck('c', 'status'),
//
//				'withdraw_latest' => DB::table('transaction')
//					->where('type', 'withdraw')
//					->where('currency', $currency)
//					->orderByDesc('id')
//					->first(['id', 'type', 'status', 'currency', 'amount_base', 'txid', 'created_at', 'updated_at']),
//			];

			return [
				'debug' => $debug,
				'range' => [
					'from' => $from->toISOString(),
					'to' => $to->toISOString(),
				],
				'filters' => [
					'currency_code' => $currency,
					'currency_symbol' => str_contains($currency, ':') ? explode(':', $currency, 2)[1] : $currency,
					'decimals' => $decimals,
					'unclaimed_days' => $unclaimedDays,
				],

				// 11)
				'volume' => [
					'deposits' => [
						'count' => $depositCount,
						'amount_base' => $depositBase,
						'amount_ui' => $depositUi,
					],
					'withdrawals' => [
						'count' => $withdrawCount,
						'amount_base' => $withdrawBase,
						'amount_ui' => $withdrawUi,
					],
				],

				// 12)
				'net_cashflow' => [
					'amount_base' => $netBase,
					'amount_ui' => $netUi,
				],

				// 13)
				'avg_deposit' => [
					'amount_base' => $avgDepositBase,
					'amount_ui' => $avgDepositUi,
				],

				// 14)
				'wallet_balances_overview' => [
					'user_wallets' => [
						'available_base' => $userTotals['available_base'],
						'reserved_base' => $userTotals['reserved_base'],
						'total_base' => $userTotals['total_base'],
						'available_ui' => $userAvailUi,
						'reserved_ui' => $userResUi,
						'total_ui' => $userTotalUi,
					],
					'non_user_wallets' => [
						'available_base' => $nonUserTotals['available_base'],
						'reserved_base' => $nonUserTotals['reserved_base'],
						'total_base' => $nonUserTotals['total_base'],
						'available_ui' => $nonAvailUi,
						'reserved_ui' => $nonResUi,
						'total_ui' => $nonTotalUi,
					],
					'note' => 'Wallet balances are computed from wallet_balances (available_base/reserved_base).',
				],

				// 15)
				'unclaimed_balances' => [
					'never_played' => $neverPlayed,
					'inactive' => $inactive,
					'note' => 'Unclaimed: deposited users who never played or are inactive (no bets) within the configured window.',
				],
			];
		}

		private function txAgg(Carbon $from, Carbon $to, string $currency, ?string $casinoId): array
		{
			$q = DB::table('transaction as t')
				->where('t.status', 'confirmed')
				->where('t.created_at', '>=', $from)
				->where('t.created_at', '<', $to)
				->where('t.currency', $currency);

			if ($casinoId) {
				$q->where('t.int_casino_id', '=', $casinoId);
			}

			$rows = $q->groupBy('t.type')
				->get([
					't.type',
					DB::raw('COUNT(*) as cnt'),
					DB::raw('COALESCE(SUM(t.amount_base),0) as sum_base'),
				]);

			$out = [
				'deposit' => ['count' => 0, 'sum_base' => '0'],
				'withdraw' => ['count' => 0, 'sum_base' => '0'],
			];

			foreach ($rows as $r) {
				$type = (string)$r->type;
				if (!isset($out[$type])) continue;
				$out[$type] = [
					'count' => (int)$r->cnt,
					'sum_base' => (string)$r->sum_base,
				];
			}

			return $out;
		}

		/**
		 * Totals din wallet_balances pe un holder type.
		 * - dacă holderType e string => doar acel holder (Player)
		 * - dacă holderType e null => non-user (<> Player)
		 */
		private function walletBalancesTotals(?string $holderType, string $currency, ?string $casinoId = null): array
		{
			$q = DB::table('wallets as w')
				->join('wallet_balances as wb', 'wb.wallet_id', '=', 'w.id')
				->where('w.currency', $currency);

			if ($holderType) {
				$q->where('w.holder_type', $holderType);
				if ($holderType === self::PLAYER_HOLDER_TYPE && $casinoId) {
					$q->join('players as p', 'p.id', '=', 'w.holder_id')
						->where('p.int_casino_id', '=', $casinoId);
				}
			} else {
				$q->where('w.holder_type', '<>', self::PLAYER_HOLDER_TYPE);
			}

			$row = $q->selectRaw("
            COALESCE(SUM(wb.available_base),0) as available_base,
            COALESCE(SUM(wb.reserved_base),0) as reserved_base
        ")->first();

			$available = (string)($row->available_base ?? '0');
			$reserved = (string)($row->reserved_base ?? '0');
			$total = $this->bcadd0($available, $reserved);

			return [
				'available_base' => $available,
				'reserved_base' => $reserved,
				'total_base' => $total,
			];
		}

		/**
		 * Unclaimed A: deposited ever, but 0 bets ever.
		 * Returnează: players_count + sums (base+ui) for available/reserved/total.
		 */
		private function calcUnclaimedNeverPlayed(string $currency, ?string $casinoId, int $decimals, int $scaleUi): array
		{
			// Users who deposited confirmed at least once (ever)
			$depositUsersQ = DB::table('transaction as t')
				->join('wallets as w', 'w.id', '=', 't.wallet_id')
				->where('t.status', 'confirmed')
				->where('t.type', 'deposit')
				->where('t.currency', $currency)
				->where('w.holder_type', self::PLAYER_HOLDER_TYPE)
				->select('w.holder_id')
				->distinct();

			if ($casinoId) {
				$depositUsersQ->where('t.int_casino_id', '=', $casinoId);
			}

			// Users who have at least one bet ever (transaction_type='bet')
			$bettedUsersQ = DB::table('bets as b')
				->where('b.transaction_type', 'bet')
				->where('b.currency', $currency)
				->select('b.user_id')
				->distinct();

			if ($casinoId) {
				$bettedUsersQ->where('b.int_casino_id', '=', $casinoId);
			}

			// deposited users NOT in betted users
			$neverPlayedUsersQ = DB::query()
				->fromSub($depositUsersQ, 'du')
				->whereNotIn('du.holder_id', $bettedUsersQ);

			$playersCount = (int)$neverPlayedUsersQ->count();

			// Sum balances for wallets of those users (currency), from wallet_balances
			$sums = $this->sumWalletBalancesForUsers(
				userIdsSubquery: $neverPlayedUsersQ->select('holder_id'),
				currency: $currency
			);

			return $this->formatBalanceSums($playersCount, $sums, $decimals, $scaleUi);
		}

		/**
		 * Unclaimed B: deposited ever, but no BET in last N days.
		 */
		private function calcUnclaimedInactive(string $currency, ?string $casinoId, int $days, int $decimals, int $scaleUi): array
		{
			$cutoff = now()->subDays($days);

			// Users who deposited confirmed at least once (ever)
			$depositUsersQ = DB::table('transaction as t')
				->join('wallets as w', 'w.id', '=', 't.wallet_id')
				->where('t.status', 'confirmed')
				->where('t.type', 'deposit')
				->where('t.currency', $currency)
				->where('w.holder_type', self::PLAYER_HOLDER_TYPE)
				->select('w.holder_id')
				->distinct();

			if ($casinoId) {
				$depositUsersQ->where('t.int_casino_id', '=', $casinoId);
			}

			// Users who bet in last N days
			$recentBettorsQ = DB::table('bets as b')
				->where('b.transaction_type', 'bet')
				->where('b.currency', $currency)
				->where('b.when_placed', '>=', $cutoff)
				->select('b.user_id')
				->distinct();

			if ($casinoId) {
				$recentBettorsQ->where('b.int_casino_id', '=', $casinoId);
			}

			$inactiveUsersQ = DB::query()
				->fromSub($depositUsersQ, 'du')
				->whereNotIn('du.holder_id', $recentBettorsQ);

			$playersCount = (int)$inactiveUsersQ->count();

			$sums = $this->sumWalletBalancesForUsers(
				userIdsSubquery: $inactiveUsersQ->select('holder_id'),
				currency: $currency
			);

			$payload = $this->formatBalanceSums($playersCount, $sums, $decimals, $scaleUi);
			$payload['inactive_days'] = $days;
			$payload['cutoff'] = $cutoff->toISOString();

			return $payload;
		}

		/**
		 * Sum available_base/reserved_base pe walleturile userilor, filtrate de o subquery cu user_id.
		 */
		private function sumWalletBalancesForUsers($userIdsSubquery, string $currency): array
		{
			$row = DB::table('wallets as w')
				->join('wallet_balances as wb', 'wb.wallet_id', '=', 'w.id')
				->where('w.holder_type', self::PLAYER_HOLDER_TYPE)
				->where('w.currency', $currency)
				->whereIn('w.holder_id', $userIdsSubquery)
				->selectRaw("
                COALESCE(SUM(wb.available_base),0) as available_base,
                COALESCE(SUM(wb.reserved_base),0) as reserved_base
            ")
				->first();

			$available = (string)($row->available_base ?? '0');
			$reserved = (string)($row->reserved_base ?? '0');
			$total = $this->bcadd0($available, $reserved);

			return [
				'available_base' => $available,
				'reserved_base' => $reserved,
				'total_base' => $total,
			];
		}

		private function formatBalanceSums(int $playersCount, array $sums, int $decimals, int $scaleUi): array
		{
			$availUi = $this->baseToUi($sums['available_base'], $decimals, $scaleUi);
			$resUi = $this->baseToUi($sums['reserved_base'], $decimals, $scaleUi);
			$totalUi = $this->baseToUi($sums['total_base'], $decimals, $scaleUi);

			return [
				'players_count' => $playersCount,
				'available_base' => $sums['available_base'],
				'reserved_base' => $sums['reserved_base'],
				'total_base' => $sums['total_base'],
				'available_ui' => $availUi,
				'reserved_ui' => $resUi,
				'total_ui' => $totalUi,
			];
		}

		/**
		 * Decimals pentru currency (din wallet_types.precision).
		 * Fallback bun: 9 (PEP de obicei SPL are 9), altfel 8.
		 */
		private function currencyDecimals(string $currency): int
		{
			$d = DB::table('wallet_types')
				->where('code', $currency)
				->value('precision');

			$d = (int)($d ?? 0);
			if ($d > 0) return $d;

			// fallback
			return $currency === 'PEP' ? 9 : 8;
		}

		/**
		 * base -> ui (string): ui = base / 10^decimals
		 */
		private function baseToUi(string $amountBase, int $decimals, int $scale = 8): string
		{
			$amountBase = $this->normIntString($amountBase);
			if ($amountBase === '0') return '0';

			$div = bcpow('10', (string)$decimals, 0);
			return bcdiv($amountBase, $div, $scale);
		}

		// ----------------- bc helpers (strings) -----------------
		private function dbCurrency(string $currency): string
		{
			$currency = strtoupper($currency);
			// dacă deja e "SOLANA:PEP" îl lași
			if (str_contains($currency, ':')) return $currency;

			// altfel îl mapezi către formatul folosit în DB
			return "SOLANA:{$currency}";
		}

		private function bcadd0(string $a, string $b): string
		{
			return bcadd($this->normIntString($a), $this->normIntString($b), 0);
		}

		private function bcsub0(string $a, string $b): string
		{
			return bcsub($this->normIntString($a), $this->normIntString($b), 0);
		}

		private function bcdiv0(string $a, string $b): string
		{
			$a = $this->normIntString($a);
			$b = $this->normIntString($b);
			if ($b === '0') return '0';
			// integer division in base units (ok pentru average base; UI va face scale)
			return bcdiv($a, $b, 0);
		}

		private function normIntString(string $v): string
		{
			$v = trim((string)$v);
			if ($v === '' || $v === null) return '0';
			// keep leading '-' if any
			$neg = false;
			if (str_starts_with($v, '-')) {
				$neg = true;
				$v = substr($v, 1);
			}
			$v = ltrim($v, '0');
			if ($v === '') $v = '0';
			return $neg ? ('-' . $v) : $v;
		}
	}
