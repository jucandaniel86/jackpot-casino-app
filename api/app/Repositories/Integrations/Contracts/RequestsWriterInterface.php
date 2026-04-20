<?php

	namespace App\Repositories\Integrations\Contracts;

	use App\Models\CasinoRequests;

	interface RequestsWriterInterface
	{
		public function write(
			string $session,
			string $brandID,
			string $apiPath,
			string $apiRequest,
			string $apiResponse,
			string $status,
			string $ts,
			string $provider,
			string $requestType,
			string $serverRequestType,
		): CasinoRequests;
	}