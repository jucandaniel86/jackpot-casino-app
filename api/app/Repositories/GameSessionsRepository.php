<?php

	namespace App\Repositories;

	use App\Enums\PlayerActivityEnums;
	use App\Interfaces\GameSessionsInterface;
	use App\Models\Session;
	use GuzzleHttp\Exception\ClientException;
	use GuzzleHttp\Handler\CurlHandler;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Str;

	class GameSessionsRepository implements GameSessionsInterface
	{
		private const RESPONSE_ERRORS = [
			"UNKNOWN" => "UNKNOWN",
			"INVALID_SESSION" => "INVALID_SESSION",
			"INVALID_OPERATION" => "INVALID_OPERATION"
		];

		private const REAL_MONEY = false;
		private const EXPIRE_DATE_DAYS = 30;
		private const SESSION_ACTIVE = 1;
		private const SESSION_DISABLED = 0;


		/**
		 * @param Request $request
		 * @return array|string[]
		 */
		public function start(Request $request): array
		{
			if (!auth('casino')->check()) {
				return [
					"code" => self::RESPONSE_ERRORS['INVALID_SESSION'],
					"message" => "User not authenticated"
				];
			}

			$Logs = new PlayerActivity($request);
			$CurrentWallet = auth('casino')->user()->currentWallet;
 
			if (!$CurrentWallet) {
				$Logs->log(PlayerActivityEnums::CREATE_PLAY_SESSION_ERROR, json_encode([
					"code" => self::RESPONSE_ERRORS['INVALID_SESSION'],
					"message" => "Invalid Wallet"
				]));
				return [
					"code" => self::RESPONSE_ERRORS['INVALID_SESSION'],
					"message" => "Invalid Wallet"
				];
			}

			if (!$request->has('game_id') || $request->get('game_id') == "") {
				$Logs->log(PlayerActivityEnums::CREATE_PLAY_SESSION_ERROR, json_encode([
					"code" => self::RESPONSE_ERRORS['INVALID_OPERATION'],
					"message" => "Invalid GAMEID"
				]));
				return [
					"code" => self::RESPONSE_ERRORS['INVALID_OPERATION']
				];
			}

			//check if session exists
			$CurrentSession = Session::query()
				->where('game_id', $request->get('game_id'))
				->where('wallet_id', $CurrentWallet->id)
				->where('user_id', auth('casino')->user()->id)
				->where('int_casino_id', $request->get('casino_id') ?? config('casino.defaultCasinoId'))
				->first();

			//if session not exists create new session
			if (!$CurrentSession) {
				$CurrentSession = Session::create([
					'user_id' => auth('casino')->user()->id,
					'wallet_id' => $CurrentWallet->id,
					'game_id' => $request->get('game_id'),
					'session' => Str::uuid(),
					'start_balance' => $CurrentWallet->balance,
					'demo' => self::REAL_MONEY,
					'ip_address' => $request->server('REMOTE_ADDR'),
					'user_agent' => $Logs->getUserAgent(),
					'expire_at' => now()->addDays(self::EXPIRE_DATE_DAYS),
					'active' => self::SESSION_ACTIVE,
					'int_casino_id' => $request->get('casino_id') ?? config('casino.defaultCasinoId')
				]);
				$Logs->log(PlayerActivityEnums::CREATE_PLAY_SESSION);
			} else {
				//if session exists
				//if session expired close current session and start new one
				if ($CurrentSession->expire_at < now()) {
					$CurrentSession->update([
						'active' => self::SESSION_DISABLED
					]);
					$Logs->log(PlayerActivityEnums::DESTROY_PLAY_SESSION, json_encode([
						'session' => $CurrentSession->session,
						'active' => self::SESSION_DISABLED
					]));

					$CurrentSession = Session::create([
						'user_id' => auth('casino')->user()->id,
						'wallet_id' => $CurrentWallet->id,
						'game_id' => $request->get('game_id'),
						'session' => Str::uuid(),
						'start_balance' => $CurrentWallet->balance,
						'demo' => self::REAL_MONEY,
						'ip_address' => $request->server('REMOTE_ADDR'),
						'user_agent' => $Logs->getUserAgent(),
						'expire_at' => now()->addDays(self::EXPIRE_DATE_DAYS),
						'active' => self::SESSION_ACTIVE,
						'int_casino_id' => $request->get('casino_id') ?? config('casino.defaultCasinoId')
					]);

					$Logs->log(PlayerActivityEnums::CREATE_PLAY_SESSION, json_encode([
						'session' => $CurrentSession->session,
						'active' => self::SESSION_ACTIVE
					]));
				} else {
					//if session is not expired, update number of days on current session
					$CurrentSession->update([
						'expire_at' => now()->addDays(self::EXPIRE_DATE_DAYS)
					]);

					$Logs->log(PlayerActivityEnums::UPDATE_PLAY_SESSION, json_encode([
						'session' => $CurrentSession->session,
						'expire_at' => now()->addDays(self::EXPIRE_DATE_DAYS)
					]));
				}
			}

			$registry = app(\App\Repositories\Integrations\Support\IntegrationsRegistry::class);
			$provider = $registry->tryIntegration('gameforge');
			//@todo : get provider from game
			if (!$provider) {
				Log::channel(config('integrations.game_forge.log_channel'))
					->warning('No provider implemented,', [
						'provider' => 'gameforge',
					]);
				return [
					"code" => self::RESPONSE_ERRORS['INVALID_SESSION'],
					"message" => "Invalid Provider"
				];
			}

			$Response = $provider->startGame($CurrentSession, auth('casino')->user(), []);

			return [
				'response' => $Response,
				"payload" => $Response,
			];
		}

		/**
		 * @param Request $request
		 * @return array
		 */
		public function startDemo(Request $request)
		{
			$registry = app(\App\Repositories\Integrations\Support\IntegrationsRegistry::class);
			$provider = $registry->tryIntegration('gameforge');
			//@todo : get provider from game
			if (!$provider) {
				Log::channel(config('integrations.game_forge.log_channel'))
					->warning('No provider implemented,', [
						'provider' => 'gameforge',
					]);
				return [
					"code" => self::RESPONSE_ERRORS['INVALID_SESSION'],
					"message" => "Invalid Provider"
				];
			}

			$Response = $provider->startDemoGame([
				'game_id' => $request->get('game_id')
			]);

			return [
				'response' => $Response,
				"payload" => $Response,
			];
		}
	}