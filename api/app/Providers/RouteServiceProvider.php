<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
	/**
	 * The path to your application's "home" route.
	 *
	 * Typically, users are redirected here after authentication.
	 *
	 * @var string
	 */
	public const HOME = '/';

	/**
	 * Define your route model bindings, pattern filters, and other route configuration.
	 */
	public function boot(): void
	{
		$throttleResponse = function (Request $request, array $headers) {
			$retryAfter = $headers['Retry-After'] ?? null;
			if (is_array($retryAfter)) {
				$retryAfter = $retryAfter[0] ?? null;
			}

			return response()->json([
				'message' => 'Too many requests. Please retry later.',
				'error' => 'rate_limited',
				'retry_after' => $retryAfter !== null ? (int)$retryAfter : null,
			], 429, $headers);
		};

		RateLimiter::for('api', function (Request $request) use ($throttleResponse) {
			return Limit::perMinute(60)
				->by($request->user()?->id ?: $request->ip())
				->response($throttleResponse);
		});

		RateLimiter::for('casino-public', function (Request $request) use ($throttleResponse) {
			return Limit::perMinute(60)
				->by('casino-public:' . $request->ip())
				->response($throttleResponse);
		});

		RateLimiter::for('casino-auth', function (Request $request) use ($throttleResponse) {
			$identity = (string)($request->input('email') ?? $request->input('username') ?? $request->input('login_token') ?? '');

			$limits = [
				Limit::perMinute(10)
					->by('casino-auth-ip:' . $request->ip())
					->response($throttleResponse),
			];

			if ($identity !== '') {
				$limits[] = Limit::perMinute(5)
					->by('casino-auth-id:' . strtolower(trim($identity)))
					->response($throttleResponse);
			}

			return $limits;
		});

		RateLimiter::for('casino-player', function (Request $request) use ($throttleResponse) {
			$playerId = (string)($request->user('casino')?->id ?: 'guest');
			return Limit::perMinute(45)
				->by('casino-player:' . $playerId . ':' . $request->ip())
				->response($throttleResponse);
		});

		RateLimiter::for('casino-gameplay', function (Request $request) use ($throttleResponse) {
			$playerId = (string)($request->user('casino')?->id ?: 'guest');
			return Limit::perMinute(20)
				->by('casino-gameplay:' . $playerId . ':' . $request->ip())
				->response($throttleResponse);
		});

		RateLimiter::for('casino-stream', function (Request $request) use ($throttleResponse) {
			return Limit::perMinute(30)
				->by('casino-stream:' . $request->ip())
				->response($throttleResponse);
		});

		RateLimiter::for('provider-callback', function (Request $request) use ($throttleResponse) {
			return Limit::perMinute(120)
				->by('provider-callback:' . $request->ip())
				->response($throttleResponse);
		});

		$this->routes(function () {
			Route::middleware('api')
				->prefix('api')
				->group(base_path('routes/api.php'));

			Route::middleware('api')
				->prefix('api')
				->group(base_path('routes/frontend-casino.php'));

			Route::middleware('web')
				->group(base_path('routes/web.php'));
		});
	}
}
