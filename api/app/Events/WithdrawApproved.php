<?php

	namespace App\Events;

	use Illuminate\Foundation\Events\Dispatchable;
	use Illuminate\Queue\SerializesModels;

	class WithdrawApproved
	{
		use Dispatchable, SerializesModels;

		public function __construct(public string $withdrawRequestUuid)
		{
		}
	}
