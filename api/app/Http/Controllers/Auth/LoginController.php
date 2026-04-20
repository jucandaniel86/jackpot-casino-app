<?php

	namespace App\Http\Controllers\Auth;

	use App\Http\Controllers\Controller;
	use App\Models\User;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Spatie\Activitylog\Facades\Activity;
	use function auth;
	use function response;

	class LoginController extends Controller
	{
		/**
		 * Handle the incoming request.
		 *
		 * @param \Illuminate\Http\Request $request
		 * @return \Illuminate\Http\Response
		 */
		public function __invoke(Request $request): JsonResponse
		{
			$User = User::where('email', $request->get('email'))->first();
			if (!$User) {

				activity()
					->causedBy(null)
					->withProperties(['email' => $request->get('email')])
					->log(config('errors.19') . ":: Invalid email address");

				return response()->json([
					'error' => 'Invalid email address'
				], 401);
			}

			try {
				if (auth()->attempt($request->only(['email', 'password']))) {
					//generate the token for the user
					$user_login_token = auth()->user()->createToken('access@' . $User->email)->accessToken;
//          Log::info($user_login_token);

					activity()
						->causedBy(auth()->user())
						->withProperties(['token' => $user_login_token])
						->log("Successful login");

					return response()->json(['token' => $user_login_token], 200);
				} else {

					activity()
						->causedBy(null)
						->withProperties(['email' => $request->get('email')])
						->log(config('errors.19') . ":: Invalid login credentials");

					return response()->json([
						'error' => "Invalid Login",
						'details' => "Invalid Login"
					], 401);
				}
			} catch (\Exception $exception) {

				activity()
					->causedBy(null)
					->withProperties(['email' => $request->get('email'), 'file' => $exception->getFile(), 'code' => $exception->getCode()])
					->log(config('errors.19') . ":: " . $exception->getMessage());

				return response()->json([
					'error' => $exception->getMessage(),
					'details' => $exception->getTrace()
				], 401);
			}

		}
	}