<?php

	namespace App\Repositories\Integrations\GameForge\Services;

	use App\Repositories\Crypto\Services\WalletLedgerService;
	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Repositories\Crypto\Support\Money;
	use App\Enums\TransactionTypes;
	use App\Events\BetCreated;
	use App\Events\BetWinCreated;
	use App\Repositories\Integrations\Contracts\IntegrationsInterface;
	use App\Repositories\Integrations\GameForge\Support\Errors;
	use App\Repositories\Integrations\Support\Signature;
	use App\Models\Bet;
	use App\Models\Casino;
	use App\Models\Player;
	use App\Models\Session;
	use App\Models\Wallet;
	use GuzzleHttp\Exception\ClientException;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Str;
	use function app;
	use function auth;
	use function config;
	use function env;
	use function now;

	class GameForgeService implements IntegrationsInterface
	{
		//SECRET KEYS
		private $secret = "";
		private $walletKey = "";

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

		private const DEPOSIT_MODAL = "wallet";
		private const RETURN_MODAL = "wallet";

		public function __construct()
		{
			$this->secret = config('integrations.game_forge.secret');
			$this->walletKey = config('integrations.game_forge.wallet_key');
		}

		public function supports(string $currency): bool
		{
			return true;
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

		private function logRequest(string $requestType, Request $request): void
		{
			$this->log([], 'info');
			$this->log([
				'requestType' => $requestType,
				'request' => $request->all(),
				'headers' => $request->header('X-REQUEST-SIGN')
			], 'info');
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

		/**
		 * @param Request $payload
		 * @return array
		 */
		public function balance(Request $request): array
		{
			$SessionID = $request->get('session_id');
			$UserID = $request->get('user_id');

			$Session = Session::query()
				->where('session', $SessionID)
				->first();

			if (!$Session) {
				return $this->errorResponse(self::BALANCE_INVALID_SESSION, 6003);
			}

			if ($Session->expire_at < now()) {
				return $this->errorResponse(self::BALANCE_INVALID_SESSION, 6004);
			}

			if ($Session->user_id != $UserID) {
				return $this->errorResponse(self::BALANCE_INVALID_SESSION, 6005);
			}

			$User = Player::query()->where('id', $Session->user_id)->first();

			if (!$User) {
				return $this->errorResponse(self::BALANCE_UNKNOWN, 6006);
			}

			$Wallet = $User->wallets()->where('id', $Session->wallet_id)->first();

			if (!$Wallet) {
				return $this->errorResponse(self::BALANCE_UNKNOWN, 6007);
			}

			$playableBalance = $this->playableBalanceUi($Wallet);

			$Response = [
				'status' => self::STATUS_SUCCESS,
				"result" => [
					"balance" => (string)$playableBalance,
					"currency" => $Wallet->currency_code,
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
				return $this->errorResponse(self::BET_INVALID_SESSION, 6003);
			}

			if ($Session->expire_at < now()) {
				return $this->errorResponse(self::BET_INVALID_SESSION, 6004);
			}

			$Wallet = Wallet::query()->where('id', $Session->wallet_id)->first();

			if ($Wallet->currency_code !== $currency) {
				return $this->errorResponse(self::BET_INVALID_SESSION, 6009);
			}

			$CurrentTransaction = Bet::where('operator_transaction_id', $transaction['transaction_id'])->first();
			$playableBalance = $this->playableBalanceUi($Wallet);

			if ($CurrentTransaction) {
				return [
					'status' => self::STATUS_SUCCESS,
					'result' => [
						'transaction_id' => (string)$transaction['transaction_id'],
						'reference_id' => (string)$CurrentTransaction->transaction_id,
						'balance' => $playableBalance,
						'currency' => $Wallet->currency_code,
					],
				];
			}

			$wallet = $Wallet;
			$decimals = CurrencyDecimals::internalForWallet($wallet);

			$amountBase = Money::uiToBase($transaction['amount'], $decimals);

			$svc = app(\App\Repositories\Crypto\Services\GameWalletService::class);
			$bsvc = app(\App\Repositories\Integrations\Services\BetsWriterService::class);

			$ctx = [
				'provider' => 'gameforge',
				'provider_tx_id' => $transaction['transaction_id'],
				'round_id' => $roundID,
				'game_id' => $gameID,
				'session_id' => $sessionID,
				'amount_ui' => (string)$transaction['amount'],
			];

			try {
				if ($transaction['type'] === TransactionTypes::BET->value) {
					$svc->placeBet($wallet, $Session, $amountBase, $decimals, $ctx);
					$bet = $bsvc->writePlaceBet(
						session: $Session,
						gameID: $gameID,
						transactionID: $transaction['transaction_id'],
						roundID: $roundID,
						currency: $currency,
						ts: $ts,
						amount: $transaction['amount'],
						roundFinished: $roundFinished,
					);

					event(new BetCreated($bet));
				}

				if ($transaction['type'] === TransactionTypes::WIN->value) {
					$svc->applyWin($wallet, $Session, $amountBase, $decimals, $ctx);
					$winBet = $bsvc->writeWin(
						session: $Session,
						gameID: $gameID,
						transactionID: $transaction['transaction_id'],
						roundID: $roundID,
						currency: $currency,
						ts: $ts,
						amount: $transaction['amount'],
						roundFinished: $roundFinished,
					);
					event(new BetWinCreated($winBet));
				}

			} catch (\RuntimeException $e) {
				if (in_array($e->getMessage(), [self::BET_INSUFFICIENT_FUNDS, 'INSUFFICIENT_FUNDS'], true)) {
					return $this->errorResponse(self::BET_INSUFFICIENT_FUNDS, 6010);
				}
				throw $e;
			}

			$playableBalance = $this->playableBalanceUi($wallet);

			$Response = [
				'status' => self::STATUS_SUCCESS,
				'result' => [
					'transaction_id' => (string)$transaction['transaction_id'],
					'reference_id' => (string)$ctx['provider_tx_id'],
					'balance' => (string)$playableBalance,
					'currency' => $wallet->currency_code,
				]
			];
			$this->log(['response' => $Response], 'info');
			return $Response;
		}

		/**
		 * @param Request $request
		 * @return array
		 */
		public function refund(Request $request): array
		{
			//variables
			$sessionID = $request->get('session_id');
			$roundID = $request->get('round_id');
			$roundFinished = $request->get('round_finished');
			$ts = $request->get('ts');
			$refTransaction = $request->get('refund_transaction');

			$Transaction = Bet::query()->where('operator_transaction_id', $refTransaction['transaction_id'])->first();

			if (!$Transaction) {
				return $this->errorResponse(self::REFUND_TRANSACTION_NOT_FOUND, 6013);
			}

			if ((string)$sessionID !== (string)$Transaction->session_id) {
				return $this->errorResponse(self::REFUND_INVALID_SESSION, 6003);
			}

			$Wallet = Wallet::query()->where('id', $Transaction->wallet_id)->first();
			$svc = app(\App\Repositories\Crypto\Services\GameWalletService::class);
			$bsvc = app(\App\Repositories\Integrations\Services\BetsWriterService::class);

			$ctx = [
				'provider' => 'gameforge',
				'provider_tx_id' => $Transaction->operator_transaction_id,
				'round_id' => $roundID,
				'game_id' => $Transaction->game_id,
				'session_id' => $sessionID,
			];
			$decimals = CurrencyDecimals::internalForWallet($Wallet);

			$balanceBefore = app(WalletLedgerService::class)
				->getAvailableUi($Wallet);

			$svc->refundBet(
				wallet: $Wallet,
				amountBase: $Transaction->stake,
				decimals: $decimals,
				ctx: $ctx,
				direction: $Transaction->transaction_type === TransactionTypes::BET->value ? 'credit' : 'debit'
			);
			$balanceAfter = app(WalletLedgerService::class)
				->getAvailableUi($Wallet);

			$RefundTransactionID = $bsvc->refund(
				transaction: $Transaction,
				roundID: $roundID,
				ts: $ts,
				roundFinished: $roundFinished,
				balanceBefore: $balanceBefore,
				balanceAfter: $balanceAfter,
				transactionType: $Transaction->transaction_type
			);

			$Response = [
				'status' => self::STATUS_SUCCESS,
				"result" => [
					"transaction_id" => $refTransaction['transaction_id'],
					"reference_id" => $RefundTransactionID
				]
			];
			$this->log(['response' => $Response], 'info');
		}

		private function playableBalanceUi(Wallet $realWallet): string
		{
			$ledger = app(WalletLedgerService::class);
			$displayScale = CurrencyDecimals::uiForWallet($realWallet);
			$realAvailable = $ledger->getAvailableUi($realWallet, $displayScale);

			$bonusWallet = Wallet::query()
				->where('holder_type', $realWallet->holder_type)
				->where('holder_id', $realWallet->holder_id)
				->where('currency_id', $realWallet->currency_id)
				->where('id', '!=', $realWallet->id)
				->whereHas('type', function ($q) {
					$q->where('purpose', 'bonus')->where('active', 1);
				})
				->first();

			if (!$bonusWallet) {
				if ((bool)env('GAMEFORGE_DEBUG_BALANCE', false)) {
					$this->log([
						'event' => 'gameforge.balance.debug',
						'real_wallet_id' => $realWallet->id,
						'scale' => $displayScale,
						'real_available' => $realAvailable,
						'bonus_wallet_id' => null,
						'bonus_available' => '0',
						'total_playable' => $realAvailable,
					], 'info');
				}
				return $realAvailable;
			}

			$bonusAvailable = $ledger->getAvailableUi($bonusWallet, $displayScale);
			$total = bcadd($realAvailable, $bonusAvailable, $displayScale);

			if ((bool)env('GAMEFORGE_DEBUG_BALANCE', false)) {
					$this->log([
						'event' => 'gameforge.balance.debug',
						'real_wallet_id' => $realWallet->id,
						'scale' => $displayScale,
						'real_available' => $realAvailable,
						'bonus_wallet_id' => $bonusWallet->id,
						'bonus_available' => $bonusAvailable,
						'total_playable' => $total,
				], 'info');
			}

			return $total;
		}

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
		 * @param Session $session
		 * @param Player $player
		 * @param array $meta
		 * @return array[]|mixed
		 */
		public function startGame(Session $session, Player $player, array $meta)
		{
			$Payload = [
				"user_info" => [
					"user_id" => (string)$session->user_id,
					"nickname" => $player->username,
					"is_demo" => (boolean)$session->demo,
					"country" => ""
				],
				"session_id" => $session->session,
				"currency" => $session->wallet->currency_code,
				"brand_id" => (string)config('integrations.game_forge.meta.brand_id'),
				"game_id" => (string)$session->game_id,
				"language" => config('integrations.game_forge.meta.language'),
				"urls" => [
					"deposit_url" => env("FRONTEND_ENDPOINT") . '?modal=' . self::DEPOSIT_MODAL,
					"return_url" => env("FRONTEND_ENDPOINT") . "?modal=" . self::RETURN_MODAL
				]
			];
			$this->log($Payload);

			$CasinoPaths = Casino::query()->where('brand_id', '=', config('integrations.game_forge.meta.brand_id'))->first();
			$paths = json_decode($CasinoPaths->casino_api_urls);

			if (isset($paths->start)) {
				return $this->sendCasinoRequest($paths->start, $Payload, null);
			}
			return [
				'error' => true
			];

//			return $this->sendRequest($Payload);
		}

		/**
		 * @param array $meta
		 * @return array[]|mixed
		 */
		public function startDemoGame(array $meta)
		{
			$Payload = [
//				"currency" => $meta['currency'] ?? config('casino.demoCurrency'),
//				"brand_id" => (string)config('integrations.game_forge.meta.brand_id'),
				"game_id" => (string)$meta['game_id'],
				"language" => config('integrations.game_forge.meta.language'),
				"urls" => [
					"deposit_url" => env("FRONTEND_ENDPOINT") . '?modal=' . self::DEPOSIT_MODAL,
					"return_url" => env("FRONTEND_ENDPOINT") . "?modal=" . self::RETURN_MODAL
				]];

			$this->log($Payload);
			return $this->sendRequest($Payload, true);
		}

	}
