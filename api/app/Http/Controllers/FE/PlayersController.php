<?php

	namespace App\Http\Controllers\FE;

	use App\Enums\PlayerActivityEnums;
	use App\Http\Controllers\Controller;
	use App\Http\Requests\PlayerProfileRequest;
	use App\Http\Requests\UserRegisterRequest;
	use App\Http\Resources\PlayerResource;
	use App\Http\Resources\WalletResource;
	use App\Interfaces\PlayersInterface;
	use App\Models\Player;
	use App\Models\PlayerProfile;
	use App\Models\PlayerLoginVerificationCode;
	use App\Repositories\PlayerActivity;
	use App\Rules\OldPasswordRule;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Auth;
	use Illuminate\Support\Carbon;
	use Illuminate\Support\Facades\Crypt;
	use Illuminate\Support\Facades\Hash;
	use Illuminate\Support\Str;

	class PlayersController extends Controller
	{
		protected PlayersInterface $players;

		public function __construct(PlayersInterface $players)
		{
			$this->players = $players;
		}

		/**
		 * @url /api/players/registration
		 * @param UserRegisterRequest $request
		 * @return JsonResponse
		 */
		public function registration(UserRegisterRequest $request): JsonResponse
		{
			return response()->json($this->players->registration($request));
		}

		/**
		 * @url /api/login
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function login(Request $request, PlayerActivity $service): JsonResponse
		{
			$request->validate([
				'email' => 'required|string',
				'password' => 'required|string',
        'casino_id' => 'required|string',
			]);

			$username = $request->get('email');
			$password = $request->get('password');
      $casinoId = $request->get('casino_id'); // deja normalizat la int_casino_id de middleware
			$credentials = [];

			if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
				$credentials = [
					'email' => $username,
					'password' => $password,
          'int_casino_id' => $casinoId,
				];
			} else {
				$credentials = [
					'username' => $username,
					'password' => $password,
          'int_casino_id' => $casinoId,
				];
			}
			$token = Auth::guard('casino')->attempt($credentials);

			if (!$token) {
				return response()->json([
					'message' => 'Wrong credentials',
				], 401);
			}

			$user = Auth::guard('casino')->user();
			Auth::guard('casino')->logout();

			$profile = PlayerProfile::query()->firstOrCreate(
				['player_id' => $user->id],
				['hide_username' => 1, 'language' => 'en', 'display_fiat_currency' => 'EUR']
			);

			$isTwoFactorEnabled = (int)$profile->two_factor_enabled === 1;
			$hasTwoFactorSecret = !empty($profile->two_factor_secret);

			if ($isTwoFactorEnabled && !$hasTwoFactorSecret) {
				$secret = $this->generateBase32Secret();
				$profile->update([
					'two_factor_secret' => Crypt::encryptString($secret),
					'two_factor_confirmed_at' => null,
				]);

				$loginToken = $this->createTwoFactorChallenge($user, $request);
				$service->log(PlayerActivityEnums::USER_LOGIN, json_encode(['step' => '2fa_setup_required']));

				return response()->json([
					'status' => '2fa_setup_required',
					'message' => 'Set up your authenticator app to complete login.',
					'login_token' => $loginToken,
					'secret' => $secret,
					'otpauth_url' => $this->buildTwoFactorOtpAuthUrl($user, $secret),
					'expires_in' => 600,
				]);
			}

			$isTwoFactorActive = $isTwoFactorEnabled && $hasTwoFactorSecret;
			if (!$isTwoFactorActive) {
				$token = Auth::guard('casino')->fromUser($user);
				if (!$token) {
					return response()->json(['message' => 'Invalid credentials'], 401);
				}

				$service->log(PlayerActivityEnums::USER_LOGIN, json_encode(['step' => 'login_without_2fa']));

				return response()->json([
					'user' => PlayerResource::make($user),
					'authorization' => [
						'token' => $token,
						'type' => 'bearer',
					]
				]);
			}

			$loginToken = $this->createTwoFactorChallenge($user, $request);

			$service->log(PlayerActivityEnums::USER_LOGIN, json_encode(['step' => '2fa_challenge_sent']));

			return response()->json([
				'status' => '2fa_required',
				'message' => 'Provide the authenticator code to complete login.',
				'login_token' => $loginToken,
				'expires_in' => 600,
			]);
		}

		/**
		 * @url /api/login/verify
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function verifyLogin(Request $request, PlayerActivity $service): JsonResponse
		{
			$request->validate([
				'login_token' => 'required|string|size:64',
				'code' => 'required|digits:6',
			]);

			$challenge = PlayerLoginVerificationCode::query()
				->where('login_token_hash', hash('sha256', $request->get('login_token')))
				->first();

			if (!$challenge || $challenge->consumed_at !== null) {
				return response()->json(['message' => 'Invalid or expired verification request.'], 401);
			}

			if ($challenge->expires_at->isPast()) {
				return response()->json(['message' => 'Verification code expired.'], 401);
			}

			if ($challenge->attempts >= $challenge->max_attempts) {
				return response()->json(['message' => 'Too many invalid attempts.'], 429);
			}

			if ($challenge->ip_address !== $request->ip()) {
				return response()->json(['message' => 'Invalid verification context.'], 401);
			}

			if ($challenge->user_agent_hash !== hash('sha256', (string)$request->userAgent())) {
				return response()->json(['message' => 'Invalid verification context.'], 401);
			}

			$user = Player::query()->find($challenge->player_id);
			if (!$user) {
				return response()->json(['message' => 'User not found.'], 404);
			}

			$profile = PlayerProfile::query()->where('player_id', $user->id)->first();
			$encryptedSecret = $profile?->two_factor_secret;
			$isEnabled = (int)($profile?->two_factor_enabled ?? 0) === 1;
			if (!$isEnabled || !$encryptedSecret) {
				return response()->json(['message' => 'Two-factor authentication is not enabled.'], 401);
			}

			try {
				$secret = Crypt::decryptString($encryptedSecret);
			} catch (\Throwable $e) {
				return response()->json(['message' => 'Invalid two-factor secret.'], 401);
			}

			if (!$this->verifyTotpCode($secret, (string)$request->get('code'))) {
				$challenge->increment('attempts');
				return response()->json(['message' => 'Invalid authenticator code.'], 401);
			}

			if ($profile && $profile->two_factor_confirmed_at === null) {
				$profile->update([
					'two_factor_confirmed_at' => Carbon::now(),
				]);
			}

			$challenge->update([
				'consumed_at' => Carbon::now(),
			]);

			$token = Auth::guard('casino')->fromUser($user);
			if (!$token) {
				return response()->json(['message' => 'Unauthorized'], 401);
			}

			$service->log(PlayerActivityEnums::USER_LOGIN, json_encode(['step' => '2fa_verified']));

			return response()->json([
				'user' => PlayerResource::make($user),
				'authorization' => [
					'token' => $token,
					'type' => 'bearer',
				]
			]);
		}

		public function twoFactorSetup(Request $request): JsonResponse
		{
			$user = $request->user('casino');
			$profile = PlayerProfile::query()->firstOrCreate(
				['player_id' => $user->id],
				['hide_username' => 1, 'language' => 'en', 'display_fiat_currency' => 'EUR']
			);

			$secret = $this->generateBase32Secret();
			$profile->update([
				'two_factor_secret' => Crypt::encryptString($secret),
				'two_factor_enabled' => 0,
				'two_factor_confirmed_at' => null,
			]);

			return response()->json([
				'success' => true,
				'secret' => $secret,
				'otpauth_url' => $this->buildTwoFactorOtpAuthUrl($user, $secret),
			]);
		}

		public function twoFactorEnable(Request $request): JsonResponse
		{
			$request->validate([
				'code' => 'required|digits:6',
			]);

			$user = $request->user('casino');
			$profile = PlayerProfile::query()->where('player_id', $user->id)->first();
			$encryptedSecret = $profile?->two_factor_secret;

			if (!$profile || !$encryptedSecret) {
				return response()->json([
					'success' => false,
					'message' => 'Two-factor setup is required first.',
				], 422);
			}

			try {
				$secret = Crypt::decryptString($encryptedSecret);
			} catch (\Throwable $e) {
				return response()->json([
					'success' => false,
					'message' => 'Invalid two-factor secret.',
				], 422);
			}

			if (!$this->verifyTotpCode($secret, (string)$request->get('code'))) {
				return response()->json([
					'success' => false,
					'message' => 'Invalid authenticator code.',
				], 422);
			}

			$profile->update([
				'two_factor_enabled' => 1,
				'two_factor_confirmed_at' => Carbon::now(),
			]);

			return response()->json([
				'success' => true,
				'message' => 'Two-factor authentication enabled.',
			]);
		}

		public function twoFactorDisable(Request $request): JsonResponse
		{
			$request->validate([
				'code' => 'required|digits:6',
			]);

			$user = $request->user('casino');
			$profile = PlayerProfile::query()->where('player_id', $user->id)->first();
			$encryptedSecret = $profile?->two_factor_secret;

			if (!$profile || !$encryptedSecret || (int)$profile->two_factor_enabled !== 1) {
				return response()->json([
					'success' => false,
					'message' => 'Two-factor authentication is not enabled.',
				], 422);
			}

			try {
				$secret = Crypt::decryptString($encryptedSecret);
			} catch (\Throwable $e) {
				return response()->json([
					'success' => false,
					'message' => 'Invalid two-factor secret.',
				], 422);
			}

			if (!$this->verifyTotpCode($secret, (string)$request->get('code'))) {
				return response()->json([
					'success' => false,
					'message' => 'Invalid authenticator code.',
				], 422);
			}

			$profile->update([
				'two_factor_secret' => null,
				'two_factor_enabled' => 0,
				'two_factor_confirmed_at' => null,
			]);

			return response()->json([
				'success' => true,
				'message' => 'Two-factor authentication disabled.',
			]);
		}

		/**
		 * @return JsonResponse
		 */
		public function logout(PlayerActivity $service)
		{
			$service->log(PlayerActivityEnums::USER_LOGOUT);
			Auth::guard('casino')->logout();
			return response()->json([
				'status' => 'success',
				'message' => 'Successfully logged out',
			]);
		}

		/**
		 * @return JsonResponse
		 */
		public function refresh()
		{
			return response()->json([
				'status' => 'success',
				'user' => Auth::guard('casino')->user(),
				'authorisation' => [
					'token' => Auth::guard('casino')->refresh(),
					'type' => 'bearer',
				]
			]);
		}

		/**
		 * @url /api/players/save-profile
		 * @param PlayerProfileRequest $request
		 * @return JsonResponse
		 */
		public function savePlayerProfile(PlayerProfileRequest $request, PlayerActivity $activity): JsonResponse
		{
			$activity->log(PlayerActivityEnums::UPDATE_PLAYER_PROFILE, json_encode($request->all()));
			return response()->json($this->players->savePlayerProfile($request));
		}

		/**
		 * @url /api/players/save-settings
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function savePlayerSettings(Request $request, PlayerActivity $activity): JsonResponse
		{
			$activity->log(PlayerActivityEnums::UPDATE_PLAYER_SETTINGS, json_encode($request->all()));
			return response()->json($this->players->savePlayerSettings($request));
		}

		/**
		 * @url /api/player/profile
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function profile(Request $request): JsonResponse
		{
			return response()->json($this->players->profile($request));
		}

		/**
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function password(Request $request, PlayerActivity $activity): JsonResponse
		{
			$request->validate([
				'password' => [
					'required_with:password_confirmation',
					'same:password_confirmation',
					'different:old_password',
					'min:6',
					'regex:/[A-Z]/',
					'regex:/[a-z]/',
					'regex:/\\d/',
					'regex:/[^A-Za-z0-9]/',
				],
				'password_confirmation' => [
					'min:6',
					'regex:/[A-Z]/',
					'regex:/[a-z]/',
					'regex:/\\d/',
					'regex:/[^A-Za-z0-9]/',
				],
				'old_password' => ['required', new OldPasswordRule()]
			], [
				'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
				'password_confirmation.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
			]);

			$request->user('casino')->update([
				'password' => Hash::make($request->get('password'))
			]);
			$activity->log(PlayerActivityEnums::PASSWORD_CHANGE);
			return response()->json([
				'success' => true,
				'message' => 'Your password was saved successfully'
			]);
		}

		/**
		 * @url /player/wallet-connect
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function walletConnectLogin(Request $request, PlayerActivity $service): JsonResponse
		{
			$request->validate(['wallet' => 'required']);

			$User = Player::where('wallet_id', $request->get('wallet'))->first();

			if (!$User) {
				return response()->json([
					'message' => 'Unauthorized',
				], 401);
			}
			$Token = Auth::guard('casino')->fromUser($User);
			if (!$Token) {
				return response()->json([
					'message' => 'Unauthorized',
				], 401);
			}
			$service->log(PlayerActivityEnums::USER_LOGIN);

			return response()->json([
				'user' => PlayerResource::make($User),
				'authorization' => [
					'token' => $Token,
					'type' => 'bearer',
				]
			]);
		}

		/**
		 * @url /api/player/wallets
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function playerWallets(Request $request): JsonResponse
		{
			return response()->json($this->players->getPlayerWallets($request));
		}

		/**
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function setCurrentWallet(Request $request): JsonResponse
		{
			$request->validate([
				'currency' => 'required'
			]);
			return response()->json($request->user()->setCurrentWallet($request->get('currency')));
		}

		/**
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function getCurrentWallet(Request $request): JsonResponse
		{
			return response()->json(WalletResource::make($request->user('casino')->currentWallet));
		}

		/**
		 * @url /api/player/favorite
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function favorite(Request $request): JsonResponse
		{
			$request->validate([
				'gameID' => 'required'
			]);
			return response()->json($this->players->toggleGameFavorite($request->gameID));
		}

		/**
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function getFavGames(Request $request): JsonResponse
		{
			return response()->json($this->players->getFavGames());
		}

		/**
		 * @url /api/profile/bets
		 * @method GET
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function bets(Request $request): JsonResponse
		{
			return response()->json($this->players->playerBet($request));
		}

		private function createTwoFactorChallenge(Player $user, Request $request): string
		{
			$loginToken = Str::random(64);

			PlayerLoginVerificationCode::query()
				->where('player_id', $user->id)
				->whereNull('consumed_at')
				->delete();

			PlayerLoginVerificationCode::query()->create([
				'player_id' => $user->id,
				'login_token_hash' => hash('sha256', $loginToken),
				// Keep column populated; TOTP code is verified against secret, not this hash.
				'code_hash' => Hash::make(Str::random(32)),
				'ip_address' => $request->ip(),
				'user_agent_hash' => hash('sha256', (string)$request->userAgent()),
				'expires_at' => Carbon::now()->addMinutes(10),
			]);

			return $loginToken;
		}

		private function buildTwoFactorOtpAuthUrl(Player $user, string $secret): string
		{
			$issuer = config('app.name', 'Casino');
			$account = $user->email ?: $user->username;

			return sprintf(
				'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
				rawurlencode($issuer),
				rawurlencode($account),
				$secret,
				rawurlencode($issuer)
			);
		}

		private function generateBase32Secret(int $bytes = 20): string
		{
			$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
			$random = random_bytes($bytes);
			$bits = '';

			for ($i = 0, $len = strlen($random); $i < $len; $i++) {
				$bits .= str_pad(decbin(ord($random[$i])), 8, '0', STR_PAD_LEFT);
			}

			$output = '';
			$chunks = str_split($bits, 5);
			foreach ($chunks as $chunk) {
				if (strlen($chunk) < 5) {
					$chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
				}

				$output .= $alphabet[bindec($chunk)];
			}

			return $output;
		}

		private function base32Decode(string $secret): ?string
		{
			$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
			$secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret));

			if ($secret === '') {
				return null;
			}

			$bits = '';
			for ($i = 0, $len = strlen($secret); $i < $len; $i++) {
				$pos = strpos($alphabet, $secret[$i]);
				if ($pos === false) {
					return null;
				}
				$bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
			}

			$output = '';
			$bytes = str_split($bits, 8);
			foreach ($bytes as $byte) {
				if (strlen($byte) < 8) {
					continue;
				}

				$output .= chr(bindec($byte));
			}

			return $output;
		}

		private function verifyTotpCode(string $secret, string $code, int $window = 1): bool
		{
			$timestamp = time();
			for ($step = -$window; $step <= $window; $step++) {
				$generated = $this->generateTotpCode($secret, $timestamp + ($step * 30));
				if ($generated !== null && hash_equals($generated, $code)) {
					return true;
				}
			}

			return false;
		}

		private function generateTotpCode(string $secret, int $timestamp): ?string
		{
			$key = $this->base32Decode($secret);
			if ($key === null || $key === '') {
				return null;
			}

			$counter = (int)floor($timestamp / 30);
			$binaryCounter = pack('N2', 0, $counter);
			$hash = hash_hmac('sha1', $binaryCounter, $key, true);
			$offset = ord(substr($hash, -1)) & 0x0F;
			$truncatedHash =
				((ord($hash[$offset]) & 0x7F) << 24) |
				((ord($hash[$offset + 1]) & 0xFF) << 16) |
				((ord($hash[$offset + 2]) & 0xFF) << 8) |
				(ord($hash[$offset + 3]) & 0xFF);

			$otp = $truncatedHash % 1000000;

			return str_pad((string)$otp, 6, '0', STR_PAD_LEFT);
		}
	}
