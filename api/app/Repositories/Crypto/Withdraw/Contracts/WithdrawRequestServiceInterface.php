<?php

	namespace App\Repositories\Crypto\Withdraw\Contracts;

	use Illuminate\Contracts\Pagination\LengthAwarePaginator;

	interface WithdrawRequestServiceInterface
	{
		public function createRequest(int $playerId, int $walletId, string $toAddress, string $amountUi, array $meta = []): string;

		public function listForPlayerLast24h(int $playerId): \Illuminate\Support\Collection;

		public function paginate(array $filters): LengthAwarePaginator;

		public function approve(string $uuid, int $adminId, ?string $note = null): void;

		public function reject(string $uuid, int $adminId, string $reason): void;

		public function complete(string $uuid, int $adminId, ?string $txid = null, ?string $note = null): void;
	}