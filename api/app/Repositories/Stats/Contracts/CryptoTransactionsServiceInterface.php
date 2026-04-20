<?php

	namespace App\Repositories\Stats\Contracts;

	use Illuminate\Contracts\Pagination\LengthAwarePaginator;

	interface CryptoTransactionsServiceInterface
	{
		public function paginate(array $filters): LengthAwarePaginator;
	}