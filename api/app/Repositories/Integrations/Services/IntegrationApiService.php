<?php

	namespace App\Repositories\Integrations\Services;

	use App\Repositories\Integrations\Contracts\IntegrationApiInterface;
	use App\Repositories\Integrations\GameForge\Support\Errors;
	use App\Repositories\Integrations\Support\Signature;
	use App\Models\CasinoRequests;
	use GuzzleHttp\Exception\ClientException;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Log;
	use function PHPUnit\Framework\callback;

	class IntegrationApiService implements IntegrationApiInterface
	{
		//SECRET KEYS
		private $secret = "";
		private $walletKey = "";

		//GENERAL STATUS
		const STATUS_SUCCESS = "success";
		const STATUS_ERROR = "error";

		//BALANCE ERROR CODES
		const BALANCE_UNKNOWN = "UNKNOWN";
		const BALANCE_INVALID_SESSION = "INVALID_SESSION";
		const BALANCE_INVALID_OPERATION = "INVALID_OPERATION";

		//BETWIN ERROR CODES
		const BET_UNKNOWN = "UNKNOWN";
		const BET_INVALID_SESSION = "INVALID_SESSION";
		const BET_INSUFFICIENT_FUNDS = "INSUFFICIENT_FUNDS";
		const BET_INVALID_OPERATION = "INVALID_OPERATION";

		//REFUND ERROR CODES
		const REFUND_UNKNOWN = "UNKNOWN";
		const REFUND_INVALID_SESSION = "INVALID_SESSION";
		const REFUND_TRANSACTION_NOT_FOUND = "TRANSACTION_NOT_FOUND";
		const REFUND_INVALID_OPERATION = "INVALID_OPERATION";

		public function __construct()
		{
			$this->secret = config('integrations.game_forge.secret');
			$this->walletKey = config('integrations.game_forge.wallet_key');
		}

		/**
		 * @param $json
		 * @param $signKey
		 * @return bool
		 */
		function validateRequest($json, $signKey)
		{
			return Signature::createSignatureDigibet($this->walletKey, json_encode($json)) == $signKey;
		}

		/**
		 * @param array $payload
		 * @param $isDemo
		 * @return array[]|mixed
		 * @throws \GuzzleHttp\Exception\GuzzleException
		 */
		private function sendRequest(array $payload, $isDemo = false)
		{
			try {
				$client = new \GuzzleHttp\Client();
				$endpoint = ($isDemo) ?
					config('integrations.game_forge.endpoint_demo') :
					config('integrations.game_forge.endpoint');

				$response = $client->post($endpoint, [
					'json' => $payload,
					'headers' => [
						'Accept' => 'application/json',
						'Content-Type' => 'application/json',
						'X-REQUEST-SIGN' => Signature::createSignatureDigibet($this->secret, json_encode($payload, JSON_FORCE_OBJECT)),
					],
				]);

				return json_decode($response->getBody());

			} catch (ClientException $exception) {
				return [
					'error' => [
						'message' => $exception->getMessage(),
						'file' => $exception->getFile(),
						'line' => $exception->getLine(),
						'code' => $exception->getCode()
					]
				];
			}
		}

		/**
		 * @param $endpoint
		 * @param array $payload
		 * @param $errFn
		 * @return mixed
		 * @throws \GuzzleHttp\Exception\GuzzleException
		 */
		private function sendCasinoRequest($endpoint, array $payload, $errFn)
		{
			try {
				$client = new \GuzzleHttp\Client();
				$response = $client->post($endpoint, [
					'json' => $payload,
					'headers' => [
						'Accept' => 'application/json',
						'Content-Type' => 'application/json',
					],
				]);

				return json_decode($response->getBody());

			} catch (ClientException $exception) {
				//@todo
				return [
					'error' => [
						'message' => $exception->getMessage(),
						'file' => $exception->getFile(),
						'line' => $exception->getLine(),
						'code' => $exception->getCode()
					]
				];
			}
		}

		/**
		 * @param $message
		 * @param string $type
		 * @return void
		 */
		private function log($message, string $type = "error"): void
		{
			switch ($type) {
				case "error":
					Log::channel(config('integrations.game_forge.log_channel'))->error(json_encode($message));
					break;
				case "info":
					Log::channel(config('integrations.game_forge.log_channel'))->info(json_encode($message));
					break;
				default:
					Log::channel(config('integrations.game_forge.log_channel'))->info(json_encode($message));
			}
		}

		private function errorResponse(string $code, int $internalErrorCode): array
		{
			return [
				'status' => self::STATUS_ERROR,
				'error' => $this->generateError($code, $internalErrorCode)
			];
		}

		/**
		 * @param string $code
		 * @param int $internalError
		 * @return array
		 */
		private function generateError(string $code, int $internalErrorCode)
		{
			$ErrorObject = [
				"code" => $code,
				"msg" => Errors::error($internalErrorCode),
				"details" => [
					[
						"code" => (string)$internalErrorCode,
						"msg" => Errors::error($internalErrorCode)
					]
				]
			];
			$this->log($ErrorObject);
			return (array)$ErrorObject;
		}

		private function pathFromSession(string $sessionID, string $requestType)
		{
			$Q = CasinoRequests::query()
				->where('session', '=', $sessionID)
				->where('request_type', 'start')
				->join('casinos', 'casino_api_requests.brand_id', '=', 'casinos.brand_id')
				->first();

			if (!$Q) {
				return false;
			}
			$paths = json_decode($Q->casino_api_urls);

			if (isset($paths->{$requestType})) {
				$path = $paths->{$requestType};
				$brandID = $Q->brand_id;
				$session = $Q->session;

				return [$path, $brandID, $session];
			}
			return false;
		}

		/**
		 * @param $payload
		 * @return array
		 */
		public function start($payload)
		{
			$brandID = $payload['brand_id'] ?? '';
			$provider = $payload['provider'] ?? 'gameforge';

			$response = $this->sendRequest($payload);

			$svc = app(\App\Repositories\Integrations\Services\RequestsWriterService::class);

			$svc->write(
				session: $payload['session_id'],
				brandID: $brandID,
				apiPath: '/start',
				apiRequest: json_encode($payload),
				apiResponse: json_encode($response),
				status: 200,
				ts: time(),
				provider: $provider,
				requestType: 'start',
				serverRequestType: "POST"
			);

			return $response;
		}

		private function logRequest(string $requestType, Request $request): void
		{
			$this->log([], 'info');
			$this->log([
				'requestType' => $requestType,
				'request' => $request->all(),
				'headers' => $request->header('X-REQUEST-SIGN')
			], 'info');
		}

		/**
		 * @param Request $request
		 * @return array
		 */
		public function balance(Request $request)
		{
			$REQUEST_SIGN = $request->header('X-REQUEST-SIGN');
			$this->logRequest('balance', $request);

			if (!$this->validateRequest($request->all(), $REQUEST_SIGN)) {
				return $this->errorResponse(self::BALANCE_INVALID_SESSION, 6001);
			}

			if (!$request->has('session_id') || (string)$request->get('session_id') === "") {
				return $this->errorResponse(self::BALANCE_INVALID_OPERATION, 6002);
			}

			if (!$request->has('user_id') || (string)$request->get('user_id') === "") {
				return $this->errorResponse(self::BALANCE_INVALID_OPERATION, 6002);
			}

			$requestType = "balance";
			$requestData = $request->all();

			[$path, $brandID, $session] = $this->pathFromSession($request->get('session_id'), $requestType);
			if (!$path) {
				return $this->errorResponse(self::BALANCE_INVALID_OPERATION, 6002);
			}

			$response = $this->sendCasinoRequest($path, $requestData, null);

			$svc = app(\App\Repositories\Integrations\Services\RequestsWriterService::class);

			$svc->write(
				session: $session,
				brandID: $brandID,
				apiPath: $request->path(),
				apiRequest: json_encode($requestData),
				apiResponse: json_encode($response),
				status: 200,
				ts: time(),
				provider: 'gameforge',
				requestType: $requestType,
				serverRequestType: "POST"
			);

			return $response;
		}

		/**
		 * @param Request $request
		 * @return array|void
		 */
		public function betwin(Request $request)
		{
			$this->logRequest('betwin', $request);

			$REQUEST_SIGN = $request->header('X-REQUEST-SIGN');

			if (!$this->validateRequest($request->all(), $REQUEST_SIGN)) {
				return $this->errorResponse(self::BET_INVALID_OPERATION, 6001);
			}

			if (!$request->has('session_id') || !$request->has('round_id') || !$request->has('round_finished') || !$request->has('currency') || !$request->has('game_id') || !$request->has('transaction')) {
				return $this->errorResponse(self::BALANCE_INVALID_SESSION, 6008);
			}

			$requestType = "betwin";
			$requestData = $request->all();

			[$path, $brandID, $session] = $this->pathFromSession($request->get('session_id'), $requestType);
			if (!$path) {
				return $this->errorResponse(self::BALANCE_INVALID_OPERATION, 6002);
			}

			$response = $this->sendCasinoRequest($path, $requestData, null);

			$svc = app(\App\Repositories\Integrations\Services\RequestsWriterService::class);

			$svc->write(
				session: $session,
				brandID: $brandID,
				apiPath: $request->path(),
				apiRequest: json_encode($requestData),
				apiResponse: json_encode($response),
				status: 200,
				ts: time(),
				provider: 'gameforge',
				requestType: $requestType,
				serverRequestType: "POST"
			);

			return $response;
		}

		/**
		 * @param Request $request
		 * @return array|void
		 */
		public function refund(Request $request)
		{
			$REQUEST_SIGN = $request->header('X-REQUEST-SIGN');
			$this->logRequest('refund', $request);

			if (!$this->validateRequest($request->all(), $REQUEST_SIGN)) {
				return $this->errorResponse(self::BALANCE_INVALID_SESSION, 6001);
			}

			if (!$request->has('session_id') || !$request->has('round_id') || !$request->has('round_finished') || !$request->has('refund_transaction')) {
				return $this->errorResponse(self::REFUND_INVALID_OPERATION, 6009);
			}

			$requestType = "refund";
			$requestData = $request->all();

			[$path, $brandID, $session] = $this->pathFromSession($request->get('session_id'), $requestType);
			if (!$path) {
				return $this->errorResponse(self::BALANCE_INVALID_OPERATION, 6002);
			}

			$response = $this->sendCasinoRequest($path, $requestData, null);

			$svc = app(\App\Repositories\Integrations\Services\RequestsWriterService::class);

			$svc->write(
				session: $session,
				brandID: $brandID,
				apiPath: $request->path(),
				apiRequest: json_encode($requestData),
				apiResponse: json_encode($response),
				status: 200,
				ts: time(),
				provider: 'gameforge',
				requestType: $requestType,
				serverRequestType: "POST"
			);

			return $response;
		}
	}
