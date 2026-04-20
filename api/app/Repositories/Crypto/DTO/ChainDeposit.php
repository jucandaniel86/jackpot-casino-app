<?php

	namespace App\Repositories\Crypto\DTO;

	class ChainDeposit
	{
		public function __construct(
			public int    $walletId,
			public string $currency,
			public string $txid,
			public string $toAddress,        // token account / owner
			public string $amountBase,       // string numeric
			public int    $decimals,
			public ?int   $blockTime,
			public string $status = 'confirmed'
		)
		{
		}
	}