<?php

	namespace App\Repositories\Crypto\Contracts;

	use Illuminate\Contracts\Pagination\LengthAwarePaginator;

	interface TransactionQueryServiceInterface
	{
		/**
		 * $filters:
		 * - holder_type (string)  ex: Player::class morph
		 * - holder_id (int)
		 * - currency (string|null)
		 * - type (string|null) deposit|withdraw
		 * - from (string|null)   YYYY-MM-DD or ISO
		 * - to (string|null)
		 * - status (string|null) optional
		 * - per_page (int|null)
		 */
		public function paginate(array $filters): LengthAwarePaginator;
	}