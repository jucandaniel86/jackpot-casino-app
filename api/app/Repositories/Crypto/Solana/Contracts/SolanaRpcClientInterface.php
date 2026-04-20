<?php

	namespace App\Repositories\Crypto\Solana\Contracts;

	interface SolanaRpcClientInterface
	{
		/**
		 * @param string $owner
		 * @param string $mint
		 * @return array
		 */
		public function getTokenAccountsByOwnerForMint(string $owner, string $mint): array;

		/**
		 * @param string $address
		 * @param string|null $until
		 * @param int $limit
		 * @return array
		 */
		public function getSignaturesForAddress(
			string  $address,
			?string $until,
			int     $limit
		): array;

		/**
		 * @param string $signature
		 * @return array|null
		 */
		public function getTransaction(string $signature): ?array;

		/**
		 * @param array $signatures
		 * @return array
		 */
		public function getSignatureStatuses(array $signatures): array;

		/**
		 * Returns SPL token account balance.
		 *
		 * @param string $tokenAccount SPL token account address
		 *
		 * @return array|null
		 * [
		 *   'amount' => string,      // base units (integer, string)
		 *   'decimals' => int,       // mint decimals
		 *   'ui_amount' => string,   // decimal string (for UI only)
		 * ]
		 */
		public function getTokenAccountBalance(string $tokenAccount): ?array;
	}