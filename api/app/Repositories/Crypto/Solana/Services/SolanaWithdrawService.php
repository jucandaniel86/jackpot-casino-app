<?php

	namespace App\Repositories\Crypto\Solana\Services;

	use App\Repositories\Crypto\Support\SecretBox;

	class SolanaWithdrawService
	{
		public function __construct(private SecretBox $box)
		{
		}

		/**
		 * Trimite SPL token din TREASURY către un recipient.
		 * amountBase = string (base units)
		 */
		public function sendSplToken(string $mint, string $toOwnerAddress, string $amountBase): string
		{
			$rpcUrl = config('solana.rpc_url');
			$treasuryOwner = config('solana.treasury.owner_address');
			$treasurySecretEnc = config('solana.treasury.secret_enc');

			if (!$treasuryOwner || !$treasurySecretEnc) {
				throw new \RuntimeException("Treasury not configured");
			}

			// Decriptăm 64 bytes ed25519 secret key
			$secret = $this->box->decrypt($treasurySecretEnc);

			// IMPORTANT: nu loga secret-ul. Îl transmitem către Node prin stdin base64.
			$secretB64 = base64_encode($secret);

			$script = base_path('solana/signer/withdraw_spl.js');

			$process = new \Symfony\Component\Process\Process([
				'node',
				$script,
				'--rpc', $rpcUrl,
				'--mint', $mint,
				'--to', $toOwnerAddress,
				'--amountBase', $amountBase,
				'--treasuryOwner', $treasuryOwner,
			]);

			$process->setInput($secretB64);
			$process->setTimeout(60);
			$process->run();

			if (!$process->isSuccessful()) {
				throw new \RuntimeException(
					"Signer failed: " . $process->getErrorOutput()
				);
			}

			$out = trim($process->getOutput());
			if ($out === '') {
				throw new \RuntimeException("Signer returned empty output");
			}

			return $out; // signature
		}
	}