<?php

	namespace App\Repositories\Crypto\DTO;

	class CreateWalletResult
	{
		public function __construct(public string $address, public array $meta)
		{
		}
	}