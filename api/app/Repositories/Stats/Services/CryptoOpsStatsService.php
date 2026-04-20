<?php

	namespace App\Repositories\Stats\Services;

	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Repositories\Crypto\Solana\Contracts\SolanaRpcClientInterface;
	use App\Models\Wallet;
	use App\Repositories\Stats\Contracts\CryptoOpsStatsServiceInterface;
	use Carbon\Carbon;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Log;

	class CryptoOpsStatsService implements CryptoOpsStatsServiceInterface
	{
		private const PLAYER_HOLDER_TYPE = 'App\\Models\\Player';

		public function __construct(
			private SolanaRpcClientInterface $rpc
		)
		{
		}

		public function report(array $filters): array
		{
			$from = Carbon::parse($filters['from']);
			$to = Carbon::parse($filters['to']);
			$casinoId = $filters['int_casino_id'] ?? null;

			// frontend poate trimite "PEP" sau "SOLANA:PEP"
			$inputCurrency = strtoupper($filters['currency_code'] ?? 'PEP');
			$currencyKey = str_contains($inputCurrency, ':') ? $inputCurrency : ("SOLANA:" . $inputCurrency);

			$errors = [];

			// mint/decimals din config (structura ta)
			$mint = config("crypto.currencies.{$currencyKey}.mint");
			$decimals = CurrencyDecimals::internalForCurrency($currencyKey);
			$uiDecimals = CurrencyDecimals::uiForCurrency($currencyKey);

			if (!$mint) {
				$errors[] = "Missing mint in config for {$currencyKey} (crypto.currencies.{$currencyKey}.mint)";
			}

			// ---------- ledger liabilities (base) ----------
			// liabilities = sum(available_base + reserved_base) pentru Player wallets pe currencyKey
			$liabilitiesQ = DB::table('wallets as w')
				->join('wallet_balances as wb', 'wb.wallet_id', '=', 'w.id')
				->where('w.holder_type', self::PLAYER_HOLDER_TYPE)
				->where('w.currency', $currencyKey);

			if ($casinoId) {
				$liabilitiesQ->join('players as p', 'p.id', '=', 'w.holder_id')
					->where('p.int_casino_id', '=', $casinoId);
			}

			$liabilitiesBase = (string)$liabilitiesQ
				->selectRaw('COALESCE(SUM(wb.available_base + wb.reserved_base),0) as s')
				->value('s');

			// ---------- treasury wallet from DB ----------
			$treasuryWalletId = $this->resolveTreasuryWalletId($currencyKey);
			$treasuryWallet = null;

			$treasuryOwner = null;
			$treasuryTokenAccount = null;

			if (!$treasuryWalletId) {
				$errors[] = "Missing treasury wallet id for {$currencyKey}. Set crypto.treasury_wallet_ids[PEP] env.";
			} else {
				$treasuryWallet = Wallet::query()->find($treasuryWalletId);
				if (!$treasuryWallet) {
					$errors[] = "Treasury wallet not found in DB: wallet_id={$treasuryWalletId}";
				} else {
					$meta = $treasuryWallet->meta ?? [];

					$treasuryOwner = $meta['owner_address'] ?? null;
					if (!$treasuryOwner) {
						$errors[] = "Treasury wallet meta missing owner_address (wallet_id={$treasuryWalletId})";
					}

					// token mint în meta poate override config (optional)
					$metaMint = $meta['token_mint'] ?? null;
					if ($metaMint && $mint && $metaMint !== $mint) {
						// nu e neapărat eroare, dar merită observat
						$errors[] = "Treasury wallet token_mint differs from config mint (meta={$metaMint}, config={$mint})";
					}
					if (!$mint && $metaMint) {
						$mint = $metaMint;
					}

					$treasuryTokenAccount = $meta['token_account'] ?? null;
				}
			}

			// ---------- on-chain treasury (base) ----------
			$onChainBase = '0';

			if ($treasuryOwner && $mint) {
				try {
					if (!$treasuryTokenAccount) {
						$tas = $this->rpc->getTokenAccountsByOwnerForMint($treasuryOwner, $mint);
						$treasuryTokenAccount = $tas[0] ?? null;

						if (!$treasuryTokenAccount) {
							$errors[] = "No token accounts found for treasury owner+mint (owner={$treasuryOwner})";
						} else {
							// cache în meta ca să nu mai cauți mereu (optional dar recomandat)
							if ($treasuryWallet) {
								$meta = $treasuryWallet->meta ?? [];
								$meta['token_account'] = $treasuryTokenAccount;
								$treasuryWallet->meta = $meta;
								$treasuryWallet->save();
							}
						}
					}

					if ($treasuryTokenAccount) {
						$bal = $this->rpc->getTokenAccountBalance($treasuryTokenAccount);
						$onChainBase = (string)($bal['amount'] ?? '0');
					}
				} catch (\Throwable $e) {
					$errors[] = "RPC error reading treasury on-chain balance: " . $e->getMessage();
					Log::error('CryptoOpsStatsService treasury RPC error', [
						'currencyKey' => $currencyKey,
						'treasuryOwner' => $treasuryOwner,
						'mint' => $mint,
						'error' => $e->getMessage(),
					]);
				}
			}

			$deltaBase = $this->bcsub0($onChainBase, $liabilitiesBase);

			// ---------- 17) deposit latency ----------
			// latency_sec = UNIX_TIMESTAMP(credited_at) - block_time
			$latenciesQ = DB::table('transaction as t')
				->where('t.type', 'deposit')
				->where('t.status', 'confirmed')
				->where('t.currency', $currencyKey)
				->whereNotNull('t.credited_at')
				->whereNotNull('t.block_time')
				->where('t.created_at', '>=', $from)
				->where('t.created_at', '<', $to);

			if ($casinoId) {
				$latenciesQ->where('t.int_casino_id', '=', $casinoId);
			}

			$latencies = $latenciesQ
				->selectRaw('(UNIX_TIMESTAMP(t.credited_at) - t.block_time) as latency_sec')
				->pluck('latency_sec')
				->map(fn($v) => (int)$v)
				->filter(fn($v) => $v >= 0)
				->values()
				->all();

			sort($latencies);
			$p50 = $this->percentile($latencies, 0.50);
			$p95 = $this->percentile($latencies, 0.95);
			$max = !empty($latencies) ? max($latencies) : 0;

			// ---------- 18) fees spending (SOL) ----------
			// fee_base = lamports, meta.kind = withdraw|sweep
			$feeQ = DB::table('transaction as t')
				->where('t.fee_currency', 'SOL')
				->where('t.created_at', '>=', $from)
				->where('t.created_at', '<', $to);

			if ($casinoId) {
				$feeQ->where('t.int_casino_id', '=', $casinoId);
			}

			$feeRow = $feeQ->selectRaw("
              COALESCE(SUM(CASE WHEN JSON_EXTRACT(t.meta,'$.kind') = '\"withdraw\"' THEN t.fee_base ELSE 0 END),0) as withdraw_fee_base,
              COALESCE(SUM(CASE WHEN JSON_EXTRACT(t.meta,'$.kind') = '\"sweep\"' THEN t.fee_base ELSE 0 END),0) as sweep_fee_base,
              COALESCE(SUM(CASE WHEN JSON_EXTRACT(t.meta,'$.kind') = '\"withdraw\"' THEN 1 ELSE 0 END),0) as withdraw_count
            ")
				->first();

			$withdrawFeeLamports = (string)($feeRow->withdraw_fee_base ?? '0');
			$sweepFeeLamports = (string)($feeRow->sweep_fee_base ?? '0');
			$withdrawCount = (int)($feeRow->withdraw_count ?? 0);

			$withdrawFeeSol = $this->baseToUi($withdrawFeeLamports, 9, 9);
			$sweepFeeSol = $this->baseToUi($sweepFeeLamports, 9, 9);

			$costPerWithdrawSol = $withdrawCount > 0
				? bcdiv($withdrawFeeSol, (string)$withdrawCount, 9)
				: '0';

			// ui for mismatch
			$onChainUi = $this->baseToUi($onChainBase, $decimals, $uiDecimals);
			$liabilitiesUi = $this->baseToUi($liabilitiesBase, $decimals, $uiDecimals);
			$deltaUi = $this->baseToUi($deltaBase, $decimals, $uiDecimals);

			return [
				'range' => [
					'from' => $from->toISOString(),
					'to' => $to->toISOString(),
				],
				'currency_key' => $currencyKey,
				'mint' => $mint,
				'decimals' => $decimals,
				'errors' => $errors,

				// 16)
				'mismatch' => [
					'treasury_wallet_id' => $treasuryWalletId,
					'treasury_owner' => $treasuryOwner,
					'treasury_token_account' => $treasuryTokenAccount,

					'treasury_onchain_base' => $onChainBase,
					'ledger_liabilities_base' => $liabilitiesBase,
					'delta_base' => $deltaBase,

					'treasury_onchain_ui' => $onChainUi,
					'ledger_liabilities_ui' => $liabilitiesUi,
					'delta_ui' => $deltaUi,
				],

				// 17)
				'deposit_latency' => [
					'count' => count($latencies),
					'p50_sec' => $p50,
					'p95_sec' => $p95,
					'max_sec' => $max,
				],

				// 18)
				'fees' => [
					'withdraw_fee_lamports' => $withdrawFeeLamports,
					'sweep_fee_lamports' => $sweepFeeLamports,
					'withdraw_fee_sol' => $withdrawFeeSol,
					'sweep_fee_sol' => $sweepFeeSol,
					'withdraw_count' => $withdrawCount,
					'cost_per_withdraw_sol' => $costPerWithdrawSol,
				],
			];
		}

		/**
		 * Map din config-ul tău:
		 * crypto.treasury_wallet_ids['PEP'] = wallet_id
		 * Dar request-ul vine ca SOLANA:PEP => extragem symbol "PEP"
		 */
		private function resolveTreasuryWalletId(string $currencyKey): ?int
		{
			// currencyKey = SOLANA:PEP => symbol = PEP
			$parts = explode(':', $currencyKey, 2);
			$symbol = $parts[1] ?? $currencyKey;

			$id = config("crypto.treasury_wallet_ids.{$symbol}");
			if (!$id) return null;

			return (int)$id;
		}

		private function percentile(array $sorted, float $p): int
		{
			$n = count($sorted);
			if ($n === 0) return 0;
			$idx = (int)floor(($n - 1) * $p);
			return (int)$sorted[$idx];
		}

		private function baseToUi(string $amountBase, int $decimals, int $scale): string
		{
			$amountBase = $this->normInt($amountBase);
			if ($amountBase === '0') return '0';

			$div = bcpow('10', (string)$decimals, 0);
			// bcdiv suportă și negative
			return bcdiv($amountBase, $div, $scale);
		}

		private function bcsub0(string $a, string $b): string
		{
			return bcsub($this->normInt($a), $this->normInt($b), 0);
		}

		private function normInt(string $v): string
		{
			$v = trim((string)$v);
			if ($v === '') return '0';

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
