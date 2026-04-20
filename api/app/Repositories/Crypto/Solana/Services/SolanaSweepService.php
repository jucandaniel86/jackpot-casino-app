<?php

	namespace App\Repositories\Crypto\Solana\Services;

		use App\Repositories\Crypto\Contracts\SweepServiceInterface;
		use App\Repositories\Crypto\Contracts\TreasuryServiceInterface;
		use App\Repositories\Crypto\Solana\Contracts\SolanaRpcClientInterface;
		use App\Repositories\Crypto\Support\CurrencyDecimals;
		use App\Repositories\Crypto\Support\Money;
		use App\Repositories\Crypto\Support\SecretBox;
		use App\Models\Wallet;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Process;
	use Illuminate\Support\Facades\Log;

	class SolanaSweepService implements SweepServiceInterface
	{
		public function __construct(
			private SolanaRpcClientInterface $rpc,
			private TreasuryServiceInterface $treasury
		)
		{
		}

		public function supports(string $currency): bool
		{
			return str_starts_with($currency, 'SOLANA:');
		}

		public function sweep(int $walletId): ?string
		{
			/** @var Wallet $wallet */
			$wallet = Wallet::query()->findOrFail($walletId);

			if (!$this->supports($wallet->currency)) {
				Log::channel('crypto')->info('Sweep skipped (unsupported currency)', [
					'wallet_id' => $wallet->id,
					'currency' => $wallet->currency,
				]);
				return null;
			}

			$meta = $wallet->meta ?? [];
			$owner = $meta['owner_address'] ?? null;
			$mint = $meta['token_mint'] ?? null;
			$tokenAccount = $meta['token_account'] ?? null;

			if (!$owner || !$mint) {
				Log::channel('crypto')->warning('Sweep skipped (missing owner/mint)', [
					'wallet_id' => $wallet->id,
					'currency' => $wallet->currency,
					'owner' => $owner,
					'mint' => $mint,
				]);
				return null;
			}

			// treasury
			$treasuryWallet = $this->treasury->getTreasuryWallet($wallet->currency);
			$tMeta = $treasuryWallet->meta ?? [];
			$treasuryOwner = $tMeta['owner_address'] ?? null;
			$treasuryTokenAccount = $tMeta['token_account'] ?? null;

			if (!$treasuryOwner) {
				throw new \RuntimeException('Treasury meta missing owner_address');
			}

			// resolve token accounts (cache recommended)
			$tas = $this->rpc->getTokenAccountsByOwnerForMint($owner, $mint);
			$tas = array_values(array_filter($tas ?? []));

			if (!$tas) {
				Log::channel('crypto')->info('Sweep skipped (no user token account)', [
					'wallet_id' => $wallet->id,
					'currency' => $wallet->currency,
					'owner' => $owner,
					'mint' => $mint,
				]);
				return null;
			}

			// pick token account with balance > 0 if possible
			$selectedTokenAccount = null;
			foreach ($tas as $ta) {
				$bal = $this->rpc->getTokenAccountBalance($ta);
				$amountBase = (string)($bal['amount'] ?? '0');
				if (bccomp($amountBase, '0', 0) === 1) {
					$selectedTokenAccount = $ta;
					break;
				}
			}

			// fallback to first if none have balance
			if (!$selectedTokenAccount) {
				$selectedTokenAccount = $tas[0];
				Log::channel('crypto')->info('Sweep token account has zero balance (using first)', [
					'wallet_id' => $wallet->id,
					'currency' => $wallet->currency,
					'token_account' => $selectedTokenAccount,
				]);
			}

			// sync stored token_account if needed
			if (!$tokenAccount || $tokenAccount !== $selectedTokenAccount) {
				$tokenAccount = $selectedTokenAccount;
				$meta['token_account'] = $tokenAccount;
				$wallet->meta = $meta;
				$wallet->save();
				Log::channel('crypto')->info('Sweep token account synced', [
					'wallet_id' => $wallet->id,
					'currency' => $wallet->currency,
					'token_account' => $tokenAccount,
				]);
			}

			if (!$treasuryTokenAccount) {
				$tas = $this->rpc->getTokenAccountsByOwnerForMint($treasuryOwner, $mint);
				$treasuryTokenAccount = $tas[0] ?? null;
				if (!$treasuryTokenAccount) {
					throw new \RuntimeException('Treasury token account not found for mint');
				}

				$tMeta['token_account'] = $treasuryTokenAccount;
				$treasuryWallet->meta = $tMeta;
				$treasuryWallet->save();
			}

				// on-chain balance of user token account
				$bal = $this->rpc->getTokenAccountBalance($tokenAccount);
				$chainAmountBase = (string)($bal['amount'] ?? '0');
				$chainDecimals = (int)($bal['decimals'] ?? 0);
				$decimals = CurrencyDecimals::internalForWallet($wallet);
				$amountBase = Money::rebaseBase($chainAmountBase, $chainDecimals, $decimals);

				Log::channel('crypto')->info('Sweep precheck', [
					'wallet_id' => $wallet->id,
					'currency' => $wallet->currency,
					'from_token_account' => $tokenAccount,
					'to_token_account' => $treasuryTokenAccount,
					'chain_amount_base' => $chainAmountBase,
					'chain_decimals' => $chainDecimals,
					'amount_base' => $amountBase,
					'decimals' => $decimals,
				]);

			// getBalance not available on RPC client here (no longer required for fee payer flow)

				if (bccomp($chainAmountBase, '0', 0) !== 1) {
					Log::channel('crypto')->info('Sweep skipped (zero balance)', [
						'wallet_id' => $wallet->id,
						'currency' => $wallet->currency,
						'token_account' => $tokenAccount,
						'chain_amount_base' => $chainAmountBase,
					]);
					return null; // nothing to sweep
				}

			// IMPORTANT: păstrează un mic buffer SOL pentru fees dacă walletul e și fee payer
			// (dacă transferul e semnat de user wallet, user wallet trebuie să aibă SOL pt fee)
			// În MVP, presupunem că ai alimentat fee payer sau folosești treasury ca fee payer (mai avansat).

			// rule node script (similar withdraw)
			$rpcUrl = config('crypto.rpc.solana', 'https://api.mainnet-beta.solana.com');
			$script = base_path('node/solana/sweep_spl.cjs'); // CommonJS for Node with "type": "module"

			$userSecretB64 = $meta['secret_b64'] ?? null;
			if (!$userSecretB64) {
				$secretEnc = $meta['secret_enc'] ?? null;
				if ($secretEnc) {
					$secret = app(SecretBox::class)->decrypt($secretEnc);
					$userSecretB64 = base64_encode($secret);
				}
			}
			if (!$userSecretB64) {
				throw new \RuntimeException('Missing user wallet secret for sweep');
			}

			$treasurySecretEnc = config('solana.treasury.secret_enc');
			if (!$treasurySecretEnc) {
				throw new \RuntimeException('Missing SOLANA_TREASURY_SECRET_ENC');
			}
			$treasurySecret = app(SecretBox::class)->decrypt($treasurySecretEnc);
			$feePayerSecretB64 = base64_encode($treasurySecret);

			$nodeBin = config('solana.node_bin', 'node');
			$result = Process::input(json_encode([
				'userSecretB64' => $userSecretB64,
				'feePayerSecretB64' => $feePayerSecretB64,
			]))->run([
				$nodeBin, $script,
				'--rpc', $rpcUrl,
				'--mint', $mint,
					'--fromOwner', $owner,
					'--fromTokenAccount', $tokenAccount,
					'--toOwner', $treasuryOwner,
					'--toTokenAccount', $treasuryTokenAccount,
					'--amountBase', $chainAmountBase,
				]);

			if (!$result->successful()) {
				Log::channel('crypto')->error('Sweep failed (node script)', [
					'wallet_id' => $wallet->id,
					'currency' => $wallet->currency,
					'error' => $result->errorOutput(),
				]);
				throw new \RuntimeException("Sweep failed: " . $result->errorOutput());
			}

			$txid = trim($result->output());
				Log::channel('crypto')->info('Sweep submitted', [
					'wallet_id' => $wallet->id,
					'currency' => $wallet->currency,
					'txid' => $txid,
					'chain_amount_base' => $chainAmountBase,
					'chain_decimals' => $chainDecimals,
					'amount_base' => $amountBase,
				]);

			// scrie transaction row (kind=sweep)
			DB::table('transaction')->insert([
				'wallet_id' => $wallet->id,
				'uuid' => (string)\Illuminate\Support\Str::uuid(),
				'currency' => $wallet->currency,
				'type' => 'sweep',
				'status' => 'pending',
				'amount_base' => $amountBase,
				'decimals' => $decimals,
				'amount' => null,
				'txid' => $txid,
				'from_address' => $owner,
				'to_address' => $treasuryOwner,
					'meta' => json_encode([
						'kind' => 'sweep',
						'mint' => $mint,
						'chain_amount_base' => $chainAmountBase,
						'chain_decimals' => $chainDecimals,
						'internal_decimals' => $decimals,
					]),
				'block_time' => null,
				'int_casino_id' => $wallet->holder->int_casino_id ?? null,
				'created_at' => now(),
				'updated_at' => now(),
			]);
				Log::channel('crypto')->info('Sweep transaction recorded', [
					'wallet_id' => $wallet->id,
					'currency' => $wallet->currency,
					'txid' => $txid,
					'chain_amount_base' => $chainAmountBase,
					'chain_decimals' => $chainDecimals,
					'amount_base' => $amountBase,
				]);

			return $txid;
		}
	}
