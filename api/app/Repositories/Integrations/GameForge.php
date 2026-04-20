<?php

	namespace App\Repositories\Integrations;

	use App\Enums\TransactionTypes;
	use App\Interfaces\IntegrationsInterface;
	use App\Models\Bet;
	use App\Models\Player;
	use App\Models\Session;
	use App\Models\Wallet;
	use Illuminate\Database\QueryException;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Str;

	class GameForge implements IntegrationsInterface
	{
		//SECRET KEYS
		private $secret = "IksJ3+A42gUR9l/80kZMU8r2af6oqgbkm7hvRs69TQ9KxjGGMSY3ZBSfr/1FAce/8QuzC2NBAsZ5paiEt8XatQ==";
		private $walletKey = "pOpue/jj5VPwa9LkaK0/I7/6S3BWrQ4Ji0cArIA4SZaJehzo6eXLT9jxPB4bard2KGNSZNLw9juMQAuiFU3fmA==";

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

		//GENERAL STATUS
		const STATUS_SUCCESS = "success";
		const STATUS_ERROR = "error";

		const OPERATOR_ID = 6;

		//INTERNAL ERRORS
		const ERRORS = [
			6001 => "INVALID SIGNATURE",
			6002 => "INVALID REQUEST PARAMS",
			6003 => "SESSION NOT FOUND",
			6004 => "SESSION EXPIRED",
			6005 => "SESSION NOT MATCH USER",
			6006 => "INVALID USER",
			6007 => "INVALID WALLET",
			6008 => 'INVALID PARAMS',
			6009 => 'INVALID WALLET CURRENCY',
			6010 => 'INSUFFICIENT FOUNDS',
			6011 => 'INVALID TRANSACTION TYPE',
			6012 => 'DATABASE ERROR',
			6013 => 'TRANSACTION NOT FOUND'
		];


		/**
		 * @param string $base64Key
		 * @param string $jsonData
		 * @return string
		 */
		private function createSignatureDigibet(string $base64Key, string $jsonData)
		{
			// Decode base64 key
			$key = base64_decode($base64Key);
			// Compute HMAC-SHA256 and return base64 result
			$hash = hash_hmac('sha256', $jsonData, $key, true);
			return base64_encode($hash);
		}

		/**
		 * @param $json
		 * @param $signKey
		 * @return bool
		 */
		function validateRequest($json, $signKey)
		{
			return $this->createSignatureDigibet($this->walletKey, json_encode($json)) == $signKey;
		}

		private function log($message, string $type = "error"): void
		{
			switch ($type) {
				case "error":
					Log::channel('wallet')->error(json_encode($message));
					break;
				case "info":
					Log::channel('wallet')->info(json_encode($message));
					break;
				default:
					Log::channel('wallet')->info(json_encode($message));
			}

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
				"details" => [
					"code" => (string)$internalErrorCode,
					"msg" => (isset(self::ERRORS[$internalErrorCode])) ? self::ERRORS[$internalErrorCode] : "INTERNAL ERROR"
				]
			];
			$this->log($ErrorObject);
			return $ErrorObject;
		}

		/**
		 * @param Request $payload
		 * @return array
		 */
		public function balance(Request $request): array
		{
			$REQUEST_SIGN = $request->header('X-REQUEST-SIGN');
			$this->log([], 'info');
			$this->log([
				'requestType' => 'balance',
				'request' => $request->all(),
				'headers' => $request->header('X-REQUEST-SIGN')
			], 'info');

			if (!$this->validateRequest($request->all(), $REQUEST_SIGN)) {
				return [
					"status" => self::STATUS_ERROR,
					"error" => $this->generateError(self::BALANCE_INVALID_SESSION, 6001)
				];
			}

			if (!$request->has('session_id') || (string)$request->get('session_id') === "") {
				return [
					"status" => self::STATUS_ERROR,
					"error" => $this->generateError(self::BALANCE_INVALID_OPERATION, 6002)
				];
			}

			if (!$request->has('user_id') || (string)$request->get('user_id') === "") {
				return [
					"status" => self::STATUS_ERROR,
					"error" => $this->generateError(self::BALANCE_INVALID_OPERATION, 6002)
				];
			}

			$SessionID = $request->get('session_id');
			$UserID = $request->get('user_id');

			$Session = Session::query()
				->where('session', $SessionID)
				->first();

			if (!$Session) {
				return [
					"status" => self::STATUS_ERROR,
					"error" => $this->generateError(self::BALANCE_INVALID_SESSION, 6003)
				];
			}

			if ($Session->expire_at < now()) {
				return [
					'status' => self::STATUS_ERROR,
					"error" => $this->generateError(self::BALANCE_INVALID_SESSION, 6004)
				];
			}

			if ($Session->user_id != $UserID) {
				return [
					'status' => self::STATUS_ERROR,
					"error" => $this->generateError(self::BALANCE_INVALID_SESSION, 6005)
				];
			}

			$User = Player::query()->where('id', $Session->user_id)->first();

			if (!$User) {
				return [
					'status' => self::STATUS_ERROR,
					"error" => $this->generateError(self::BALANCE_UNKNOWN, 6006)
				];
			}

			$Wallet = $User->wallets()->where('id', $Session->wallet_id)->first();

			if (!$Wallet) {
				return [
					'status' => self::STATUS_ERROR,
					"error" => $this->generateError(self::BALANCE_UNKNOWN, 6007)
				];
			}

			$Response = [
				'status' => self::STATUS_SUCCESS,
				"result" => [
					"balance" => (string)$Wallet->balance,
					"currency" => $Wallet->currency,
				]
			];

			$this->log(['response' => $Response], 'info');

			return $Response;
		}

		/**
		 * @param Request $request
		 * @return array
		 */
		public function bet(Request $request): array
		{
			$this->log([], 'info');
			$this->log([
				'requestType' => 'betwin',
				'request' => $request->all(),
				'headers' => $request->header('X-REQUEST-SIGN')
			], 'info');

			$REQUEST_SIGN = $request->header('X-REQUEST-SIGN');

			if (!$this->validateRequest($request->all(), $REQUEST_SIGN)) {
				return [
					"status" => self::STATUS_ERROR,
					"error" => $this->generateError(self::BET_INVALID_OPERATION, 6001)
				];
			}

			if (!$request->has('session_id') || !$request->has('round_id') || !$request->has('round_finished') || !$request->has('currency') || !$request->has('game_id') || !$request->has('transaction')) {
				return [
					"status" => self::STATUS_ERROR,
					"error" => $this->generateError(self::BALANCE_INVALID_SESSION, 6008)
				];
			}

			//variables
			$sessionID = $request->get('session_id');
			$ts = $request->get('ts');
			$roundID = $request->get('round_id');
			$roundFinished = $request->get('round_finished');
			$currency = $request->get('currency');
			$gameID = $request->get('game_id');
			$transaction = $request->get('transaction');

			$Session = Session::query()
				->where('session', $sessionID)
				->first();

			if (!$Session) {
				return [
					"status" => self::STATUS_ERROR,
					"error" => $this->generateError(self::BET_INVALID_SESSION, 6003)
				];
			}

			if ($Session->expire_at < now()) {
				return [
					'status' => self::STATUS_ERROR,
					"error" => $this->generateError(self::BET_INVALID_SESSION, 6004)
				];
			}

			$Wallet = Wallet::query()->where('id', $Session->wallet_id)->first();

			if ($Wallet->currency !== $currency) {
				return [
					'status' => self::STATUS_ERROR,
					"error" => $this->generateError(self::BET_INVALID_SESSION, 6009)
				];
			}

			$CurrentTransaction = Bet::where('operator_transaction_id', $transaction['transaction_id'])->first();

			if ($CurrentTransaction) {
				return [
					'status' => self::STATUS_SUCCESS,
					'result' => [
						'transaction_id' => (string)$transaction['transaction_id'],
						'reference_id' => (string)$CurrentTransaction->transaction_id,
						'balance' => $Wallet->balance,
						'currency' => $Wallet->currency,
					],
				];
			}

			try {
				switch ($transaction['type']) {
					case TransactionTypes::BET->value:
						if (!$Wallet->canWithdraw($transaction['amount'])) {
							return [
								'status' => self::STATUS_ERROR,
								"error" => $this->generateError(self::BET_INSUFFICIENT_FUNDS, 6010)
							];
						}
						$BalanceBefore = $Wallet->balance;
						$Wallet->minusBalance($transaction['amount']);
						$BalanceAfter = $Wallet->balance;

						$Bet = Bet::create([
							'transaction_id' => Str::uuid(),
							'session_id' => $Session->session,
							'wallet_id' => $Session->wallet_id,
							'user_id' => $Session->user_id,
							'game_id' => $gameID,
							'operator_transaction_id' => $transaction['transaction_id'],
							'operator_round_id' => $roundID,
							'currency' => $currency,
							'ts' => $ts,
							'refund_ts' => 0,
							'balance_before' => $BalanceBefore,
							'balance_after' => $BalanceAfter,
							'stake' => $transaction['amount'],
							'payout' => 0,
							'refund' => 0,
							'transaction_type' => TransactionTypes::BET->value,
							'round_finished' => $roundFinished,
							'when_placed' => now(),
						]);

						$Response = [
							'status' => self::STATUS_SUCCESS,
							'result' => [
								'transaction_id' => (string)$transaction['transaction_id'],
								'reference_id' => (string)$Bet->transaction_id,
								'balance' => (string)$BalanceAfter,
								'currency' => $currency,
							],
						];
						$this->log(['response' => $Response], 'info');
						return $Response;
					case TransactionTypes::WIN->value:
						$BalanceBefore = $Wallet->balance;
						$Wallet->addBalance($transaction['amount']);
						$BalanceAfter = $Wallet->balance;

						$Bet = Bet::create([
							'transaction_id' => Str::uuid(),
							'session_id' => $Session->session,
							'wallet_id' => $Session->wallet_id,
							'user_id' => $Session->user_id,
							'game_id' => $gameID,
							'operator_transaction_id' => $transaction['transaction_id'],
							'operator_round_id' => $roundID,
							'currency' => $currency,
							'ts' => $ts,
							'refund_ts' => 0,
							'balance_before' => $BalanceBefore,
							'balance_after' => $BalanceAfter,
							'stake' => 0,
							'payout' => $transaction['amount'],
							'refund' => 0,
							'transaction_type' => TransactionTypes::WIN->value,
							'round_finished' => $roundFinished,
							'when_placed' => now(),
						]);

						$Response = [
							'status' => self::STATUS_SUCCESS,
							'result' => [
								'transaction_id' => (string)$transaction['transaction_id'],
								'reference_id' => (string)$Bet->transaction_id,
								'balance' => (string)$BalanceAfter,
								'currency' => $currency,
							],
						];
						$this->log(['response' => $Response], 'info');
						return $Response;
					default:
						return [
							'status' => self::STATUS_ERROR,
							'error' => $this->generateError(self::BET_UNKNOWN, 6011)
						];
				}

			} catch (QueryException $exception) {
				Log::error($exception->getMessage());
				return [
					'status' => self::STATUS_ERROR,
					'error' => $this->generateError(self::BET_UNKNOWN, 6012)
				];
			}


		}

		/**
		 * @param Request $request
		 * @return array
		 */
		public function refund(Request $request): array
		{
			$REQUEST_SIGN = $request->header('X-REQUEST-SIGN');
			$this->log([], 'info');
			$this->log([
				'requestType' => 'refund',
				'request' => $request->all(),
				'headers' => $request->header('X-REQUEST-SIGN')
			], 'info');

			if (!$this->validateRequest($request->all(), $REQUEST_SIGN)) {
				return [
					"status" => self::STATUS_ERROR,
					"error" => $this->generateError(self::BALANCE_INVALID_SESSION, 6001)
				];
			}

			if (!$request->has('session_id') || !$request->has('round_id') || !$request->get('round_finished') || !$request->has('refund_transaction')) {
				return [
					"status" => self::STATUS_ERROR,
					"error" => $this->generateError(self::REFUND_INVALID_OPERATION, 6009)
				];
			}

			//variables
			$sessionID = $request->get('session_id');
			$roundID = $request->get('round_id');
			$roundFinished = $request->get('round_finished');
			$ts = $request->get('ts');
			$refTransaction = $request->get('refund_transaction');

			$Transaction = Bet::query()->where('operator_transaction_id', $refTransaction['transaction_id'])->first();

			if (!$Transaction) {
				return [
					'status' => self::STATUS_ERROR,
					'error' => $this->generateError(self::REFUND_TRANSACTION_NOT_FOUND, 6013)
				];
			}
			//@todo  : check if session is expired
			if ((string)$sessionID !== (string)$Transaction->session_id) {
				return [
					'status' => self::STATUS_ERROR,
					'error' => $this->generateError(self::REFUND_INVALID_SESSION, 6003)
				];
			}

			$Wallet = Wallet::query()->where('id', $Transaction->wallet_id)->first();

			switch ($Transaction->transaction_type) {
				case TransactionTypes::BET->value:
					{
						$BalanceBefore = $Wallet->balance;
						$Wallet->addBalance($Transaction->stake);
						$BalanceAfter = $Wallet->balance;

						$RefundTransaction = Bet::create([
							'transaction_id' => Str::uuid(),
							'session_id' => $Transaction->session_id,
							'wallet_id' => $Transaction->wallet_id,
							'user_id' => $Transaction->user_id,
							'game_id' => $Transaction->game_id,
							'operator_transaction_id' => $Transaction->operator_transaction_id,
							'operator_round_id' => $roundID,
							'currency' => $Transaction->currency,
							'ts' => $ts,
							'refund_ts' => 0,
							'balance_before' => $BalanceBefore,
							'balance_after' => $BalanceAfter,
							'stake' => 0,
							'payout' => $Transaction->stake,
							'refund_value' => 0,
							'transaction_type' => TransactionTypes::REFUND->value,
							'round_finished' => $roundFinished,
							'when_placed' => now(),
						]);

						$Transaction->update([
							'refund_transaction_id' => $RefundTransaction->transaction_id,
							'refund' => $Transaction->stake
						]);
					}

					$Response = [
						'status' => self::STATUS_SUCCESS,
						"result" => [
							"transaction_id" => $refTransaction['transaction_id'],
							"reference_id" => $RefundTransaction->transaction_id
						]
					];
					$this->log(['response' => $Response], 'info');
					return $Response;

				case TransactionTypes::WIN->value:
					{
						$BalanceBefore = $Wallet->balance;
						$Wallet->minusBalance($Transaction->payout);
						$BalanceAfter = $Wallet->balance;

						$RefundTransaction = Bet::create([
							'transaction_id' => Str::uuid(),
							'session_id' => $Transaction->session_id,
							'wallet_id' => $Transaction->wallet_id,
							'user_id' => $Transaction->user_id,
							'game_id' => $Transaction->game_id,
							'operator_transaction_id' => $Transaction->operator_transaction_id,
							'operator_round_id' => $roundID,
							'currency' => $Transaction->currency,
							'ts' => $ts,
							'refund_ts' => 0,
							'balance_before' => $BalanceBefore,
							'balance_after' => $BalanceAfter,
							'stake' => $Transaction->stake,
							'payout' => 0,
							'refund_value' => 0,
							'transaction_type' => TransactionTypes::REFUND->value,
							'round_finished' => $roundFinished,
							'when_placed' => now(),
						]);

						$Transaction->update([
							'refund_transaction_id' => $RefundTransaction->transaction_id,
							'refund' => $Transaction->stake
						]);
					}

					$Response = [
						'status' => self::STATUS_SUCCESS,
						"result" => [
							"transaction_id" => $refTransaction['transaction_id'],
							"reference_id" => $RefundTransaction->transaction_id
						]
					];
					$this->log(['response' => $Response], 'info');
					return $Response;
				default:
					{
						return [
							'status' => self::STATUS_ERROR,
							'error' => $this->generateError(self::REFUND_UNKNOWN, 6011)
						];
					}
			}

		}
	}
