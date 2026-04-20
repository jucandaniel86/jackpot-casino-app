<?php

	namespace App\Repositories\Crypto\DTO;
	class ScanResult
	{
		public function __construct(public int $newDeposits = 0)
		{
		}
	}