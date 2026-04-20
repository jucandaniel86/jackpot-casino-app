<?php

	namespace App\Events;

	use Illuminate\Foundation\Events\Dispatchable;
	use Illuminate\Queue\SerializesModels;

	class WithdrawRequested
	{
		use Dispatchable, SerializesModels;

		public function __construct(
			public string  $withdrawRequestUuid,
			public ?int    $playerId = null,
			public ?int    $walletId = null,
			public ?string $currency = null,
			public ?string $amountUi = null,
			public ?string $toAddress = null
		)
		{
		}
	}