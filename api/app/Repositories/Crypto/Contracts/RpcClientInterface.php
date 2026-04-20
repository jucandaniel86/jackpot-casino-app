<?php

	namespace App\Repositories\Crypto\Contracts;

	interface RpcClientInterface
	{
		public function getTokenAccountsByOwnerForMint(string $owner, string $mint): array;

		public function getSignaturesForAddress(
			string  $address,
			?string $until,
			int     $limit
		): array;

		public function getTransaction(string $signature): ?array;
	}