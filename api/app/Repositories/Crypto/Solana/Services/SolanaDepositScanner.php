<?php

	namespace App\Repositories\Crypto\Solana\Services;

		use App\Repositories\Crypto\Contracts\DepositScannerInterface;
		use App\Repositories\Crypto\DTO\ScanResult;
		use App\Repositories\Crypto\Support\CurrencyDecimals;
		use App\Repositories\Crypto\Support\Money;
		use App\Repositories\Crypto\Services\WalletLedgerService;
		use App\Repositories\Crypto\Solana\Contracts\SolanaRpcClientInterface;
		use App\Jobs\SweepSolanaJob;
	use App\Models\Wallet;
	use Illuminate\Support\Facades\Log;

	class SolanaDepositScanner implements DepositScannerInterface
	{
		public function __construct(
			private SolanaRpcClientInterface $rpc,
			private WalletLedgerService      $ledger
		)
		{
		}

		public function supports(string $currency): bool
		{
			return str_starts_with($currency, 'SOLANA:');
		}

		public function scan(Wallet $wallet): ScanResult
		{
			$meta = $wallet->meta ?? [];

			$owner = $meta['owner_address'] ?? null;
			$mint = $meta['token_mint'] ?? null;

			if (!$owner || !$mint) {
				return new ScanResult(0);
			}

			// Ensure balance snapshot exists
			$this->ledger->ensureBalanceRow($wallet);

			/** @var \App\Repositories\Crypto\Contracts\TransactionWriterInterface $txWriter */
			$txWriter = app(\App\Repositories\Crypto\Services\TransactionWriter::class);

			// 1) token account cache (ATA or any token account for mint)
			$tokenAccount = $meta['token_account'] ?? null;
			if (!$tokenAccount) {
				try {
					$tas = $this->rpc->getTokenAccountsByOwnerForMint($owner, $mint);
				} catch (\RuntimeException $e) {
					Log::channel('crypto')->error('solana.scan.invalid_mint', [
						'wallet_id' => $wallet->id,
						'currency' => $wallet->currency,
						'owner' => $owner,
						'mint' => $mint,
						'error' => $e->getMessage(),
					]);
					return new ScanResult(0);
				}
				if (!$tas) {
					return new ScanResult(0);
				}

				$tokenAccount = $tas[0];
				$meta['token_account'] = $tokenAccount;

				$wallet->meta = $meta;
				$wallet->save();
			}

			$lastSig = $meta['last_signature'] ?? null;
			$limit = (int)config('solana.scan.signature_limit', 50);

			// 2) signatures
			$sigs = $this->rpc->getSignaturesForAddress($tokenAccount, $lastSig, $limit);
			if (!$sigs) {
				return new ScanResult(0);
			}

			// oldest -> newest
			$sigs = array_reverse($sigs);

			$newDeposits = 0;

			foreach ($sigs as $s) {
				$sig = $s['signature'] ?? null;
				if (!$sig) continue;

				// Cursor: dacă RPC îți returnează iar lastSig, îl ignori
				if ($lastSig && $sig === $lastSig) continue;

				$tx = $this->rpc->getTransaction($sig);
				if (!$tx) {
					$lastSig = $sig;
					continue;
				}

				$delta = $this->extractDeltaToTokenAccount($tx, $tokenAccount, $mint);
				if (!$delta) {
					$lastSig = $sig;
					continue;
				}

					$chainAmountBase = (string)$delta['amount'];
					$chainDecimals = (int)$delta['decimals'];
					$decimals = CurrencyDecimals::internalForWallet($wallet);
					$amountBase = Money::rebaseBase($chainAmountBase, $chainDecimals, $decimals);
					$blockTime = isset($tx['blockTime']) ? (int)$tx['blockTime'] : null;

				// 3) ledger credit (idempotent)
				$idempotencyKey = "deposit:solana:{$sig}:{$wallet->id}";

				$isNew = $this->ledger->creditAvailable(
					wallet: $wallet,
					type: 'deposit',
					amountBase: $amountBase,
					decimals: $decimals,
					idempotencyKey: $idempotencyKey,
					referenceType: 'solana',
					referenceId: $sig,
						meta: [
							'mint' => $mint,
							'token_account' => $tokenAccount,
							'owner_address' => $owner,
							'block_time' => $blockTime,
							'chain_amount_base' => $chainAmountBase,
							'chain_decimals' => $chainDecimals,
							'internal_decimals' => $decimals,
						]
					);

				if ($isNew) {
					$newDeposits++;

					try {
						$txWriter->writeDepositTransaction(
							wallet: $wallet,
							status: 'confirmed',
							amountBase: $amountBase,
							decimals: $decimals,
							txid: $sig,
							fromAddress: $fromOwner ?? null,
								meta: [
									'mint' => $mint,
									'slot' => $slot ?? null,
									'scanner' => 'SolanaDepositScanner',
									'block_time' => $blockTime,
									'chain_amount_base' => $chainAmountBase,
									'chain_decimals' => $chainDecimals,
									'internal_decimals' => $decimals,
								]
							);
					} catch (\Throwable $e) {
						Log::channel('crypto')->error('writeDepositTransaction failed', [
							'wallet_id' => $wallet->id,
							'sig' => $sig,
							'error' => $e->getMessage(),
						]);
					}


					event(new \App\Events\DepositDetected(
						wallet: $wallet,
						amountBase: $amountBase,
						decimals: $decimals,
						txid: $sig
					));

					// Sweep is triggered via DepositDetected listener

				}

				// avansează cursor
				$lastSig = $sig;
			}

			// 4) persist cursor/state
			$meta['last_signature'] = $lastSig;
			$wallet->meta = $meta;
			$wallet->save();

			return new ScanResult($newDeposits);
		}

		/**
		 * Extrage cât s-a CREDITAT în tokenAccount pentru mint (post - pre).
		 * Folosește mapping accountIndex -> accountKeys ca să fie sigur că e tokenAccount-ul țintă.
		 */
		private function extractDeltaToTokenAccount(array $tx, string $tokenAccount, string $mint): ?array
		{
			$meta = $tx['meta'] ?? null;
			$msg = $tx['transaction']['message'] ?? null;
			if (!$meta || !$msg) return null;

			$accountKeys = $msg['accountKeys'] ?? [];
			$idxToKey = [];
			foreach ($accountKeys as $i => $k) {
				$pub = is_array($k) ? ($k['pubkey'] ?? null) : $k;
				if ($pub) $idxToKey[$i] = $pub;
			}

			$pre = [];
			foreach (($meta['preTokenBalances'] ?? []) as $b) {
				if (($b['mint'] ?? '') !== $mint) continue;
				$idx = $b['accountIndex'] ?? null;
				if ($idx === null) continue;

				if (($idxToKey[$idx] ?? '') !== $tokenAccount) continue;
				$pre[$idx] = $b['uiTokenAmount']['amount'] ?? "0";
			}

			foreach (($meta['postTokenBalances'] ?? []) as $b) {
				if (($b['mint'] ?? '') !== $mint) continue;
				$idx = $b['accountIndex'] ?? null;
				if ($idx === null) continue;

				if (($idxToKey[$idx] ?? '') !== $tokenAccount) continue;

				$post = $b['uiTokenAmount']['amount'] ?? "0";
				$dec = (int)($b['uiTokenAmount']['decimals'] ?? 0);
				$preAmt = $pre[$idx] ?? "0";

				// post > pre => incoming
				if (bccomp($post, $preAmt, 0) === 1) {
					return [
						'amount' => bcsub($post, $preAmt, 0),
						'decimals' => $dec
					];
				}
			}

			return null;
		}
	}
