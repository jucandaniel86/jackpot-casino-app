<?php

	namespace App\Repositories\Integrations\Services;

	use App\Enums\TransactionTypes;
	use App\Repositories\Integrations\Contracts\BetsWriterInterface;
	use App\Repositories\Integrations\Contracts\RequestsWriterInterface;
	use App\Models\Bet;
	use App\Models\CasinoRequests;
	use App\Models\Session;
	use Illuminate\Support\Str;

	class RequestsWriterService implements RequestsWriterInterface
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
		): CasinoRequests
		{
			return CasinoRequests::create([
				'session' => $session,
				'brand_id' => $brandID,
				'api_path' => $apiPath,
				'api_request' => $apiRequest,
				'api_response' => $apiResponse,
				'ts' => $ts,
				'status' => $status,
				'provider' => $provider,
				'request_type' => $requestType,
				'server_request_type' => $serverRequestType
			]);
		}
	}