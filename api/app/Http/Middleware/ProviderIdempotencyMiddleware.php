<?php

	namespace App\Http\Middleware;

	use App\Models\ProviderIdempotency;
	use Closure;
	use Illuminate\Http\Request;

	class ProviderIdempotencyMiddleware
	{
		public function handle(Request $request, Closure $next, string $provider, string $endpoint)
		{
			$key = $this->buildKey($request, $provider, $endpoint);

			$response = $next($request);

			if ($key) {
				$content = $response->getContent();
				$decoded = json_decode($content, true);

				ProviderIdempotency::query()->create(
					[
						'key' => $key,
						'provider' => $provider,
						'endpoint' => $endpoint,
						'http_status' => $response->getStatusCode(),
						'response_json' => $decoded,
					]
				);
			}

			return $response;
		}

		private function buildKey(Request $request, string $provider, string $endpoint): ?string
		{
			$payload = $request->all();

			return match ($endpoint) {
				'bet' => !empty($payload['transaction']['transaction_id'])
					? "{$provider}:bet:" . (string)$payload['transaction']['transaction_id']
					: null,

				'refund' => !empty($payload['refund_transaction']['transaction_id'])
					? "{$provider}:refund:" . (string)$payload['refund_transaction']['transaction_id']
					: null,

				'balance' => !empty($payload['session_id'])
					? "{$provider}:balance:" . (string)$payload['session_id']
					: null,

				default => null,
			};
		}
	}