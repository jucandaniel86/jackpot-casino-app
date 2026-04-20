<?php

	namespace App\Repositories\Crypto\DTO;

	use App\Models\Wallet;
	use App\Models\WalletType;
	use App\Models\WalletBalance;

	class WalletView
	{
		public function __construct(
			public Wallet         $wallet,
			public ?WalletType    $type,
			public ?WalletBalance $balance,
			public int            $decimals,
			public bool           $canWithdraw = true
		)
		{
		}
	}
