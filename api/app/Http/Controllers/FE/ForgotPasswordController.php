<?php

	namespace App\Http\Controllers\FE;

	use App\Http\Controllers\Controller;
	use App\Models\Player;
	use App\Notifications\PlayerResetPasswordNotification;
	use App\Support\MailSettings;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Hash;
	use Illuminate\Support\Facades\Password;

	class ForgotPasswordController extends Controller
	{
		public function requestReset(Request $request): JsonResponse
		{
			$data = $request->validate([
				'email' => ['required', 'email'],
				'casino_id' => ['required', 'string'],
			]);

			$player = Player::query()
				->where('email', $data['email'])
				->where('int_casino_id', $data['casino_id'])
				->first();

			if (!$player) {
				return response()->json([
					'success' => false,
					'message' => 'Email not found.',
				], 404);
			}

			$token = Password::broker('players')->createToken($player);

			$mailConfig = MailSettings::resolve($player->int_casino_id);
			$baseUrl = $mailConfig['url'] ?? config('app.url');
			$resetUrl = rtrim($baseUrl, '/') . '/reset-password'
				. '?token=' . urlencode($token)
				. '&email=' . urlencode($player->email)
				. '&casino=' . urlencode((string)$player->int_casino_id);

			$player->notify(new PlayerResetPasswordNotification($resetUrl, (string)$player->int_casino_id));

			return response()->json([
				'success' => true,
				'message' => 'Reset link sent.',
			]);
		}

		public function validateToken(Request $request): JsonResponse
		{
			$data = $request->validate([
				'email' => ['required', 'email'],
				'token' => ['required', 'string'],
				'casino_id' => ['required', 'string'],
			]);

			$player = Player::query()
				->where('email', $data['email'])
				->where('int_casino_id', $data['casino_id'])
				->first();

			if (!$player) {
				return response()->json(['valid' => false], 404);
			}

			$valid = Password::broker('players')->tokenExists($player, $data['token']);

			return response()->json(['valid' => (bool)$valid]);
		}

		public function reset(Request $request): JsonResponse
		{
			$data = $request->validate([
				'email' => ['required', 'email'],
				'token' => ['required', 'string'],
				'password' => [
					'required_with:password_confirmation',
					'same:password_confirmation',
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
				'casino_id' => ['required', 'string'],
			], [
				'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
			]);

			$player = Player::query()
				->where('email', $data['email'])
				->where('int_casino_id', $data['casino_id'])
				->first();

			if (!$player) {
				return response()->json([
					'success' => false,
					'message' => 'Invalid user.',
				], 404);
			}

			$status = Password::broker('players')->reset(
				[
					'email' => $player->email,
					'password' => $data['password'],
					'token' => $data['token'],
				],
				function ($user, $password) {
					$user->forceFill([
						'password' => Hash::make($password),
					])->save();
				}
			);

			if ($status !== Password::PASSWORD_RESET) {
				return response()->json([
					'success' => false,
					'message' => 'Invalid or expired token.',
				], 400);
			}

			return response()->json([
				'success' => true,
				'message' => 'Password updated successfully.',
			]);
		}
	}