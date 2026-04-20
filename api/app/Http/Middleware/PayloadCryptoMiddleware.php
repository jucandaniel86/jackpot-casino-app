<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PayloadCryptoMiddleware
{
	private const IV_LENGTH = 12;
	private const TAG_LENGTH = 16;
	private const VERSION_PREFIX = 'v1:';

	public function handle(Request $request, Closure $next, string $mode = 'both'): Response
	{
		$useEncryptedTransport = $this->shouldUseEncryptedTransport($request);

		if ($useEncryptedTransport && $this->shouldHandleRequest($mode) && !$this->decryptRequestPayload($request)) {
			return response()->json(['message' => 'Invalid encrypted payload.'], 400);
		}

		$response = $next($request);

		if (!$useEncryptedTransport || !$this->shouldHandleResponse($mode)) {
			return $response;
		}

		if (!$response instanceof JsonResponse) {
			return $response;
		}

		$plaintext = (string)$response->getContent();
		if ($plaintext === '') {
			return $response;
		}

		try {
			$encryptedPayload = self::VERSION_PREFIX . $this->encrypt($plaintext, $this->resolveKey(), $this->resolveAad($request));
		} catch (\Throwable) {
			return response()->json(['message' => 'Encryption failed.'], 500);
		}

		return response()->json(
			['payload' => $encryptedPayload],
			$response->getStatusCode(),
			$response->headers->all()
		);
	}

	private function decryptRequestPayload(Request $request): bool
	{
		if ($request->isMethod('GET') || $request->isMethod('HEAD') || $request->isMethod('OPTIONS')) {
			return true;
		}

		$raw = (string)$request->getContent();
		if ($raw === '') {
			return true;
		}

		$decoded = json_decode($raw, true);
		if (!is_array($decoded) || !isset($decoded['payload']) || !is_string($decoded['payload'])) {
			return false;
		}

		$payload = $decoded['payload'];
		if (str_starts_with($payload, self::VERSION_PREFIX)) {
			$payload = substr($payload, strlen(self::VERSION_PREFIX));
		}

		if (!is_string($payload) || $payload === '') {
			return false;
		}

		try {
			$plaintext = $this->decrypt($payload, $this->resolveKey(), $this->resolveAad($request));
		} catch (\Throwable) {
			return false;
		}

		$data = json_decode($plaintext, true);
		if (!is_array($data)) {
			return false;
		}

		$request->replace($data);
		return true;
	}

	private function encrypt(string $plaintext, string $key, string $aad = ''): string
	{
		$iv = random_bytes(self::IV_LENGTH);
		$tag = '';

		$ciphertext = openssl_encrypt(
			$plaintext,
			'aes-256-gcm',
			$key,
			OPENSSL_RAW_DATA,
			$iv,
			$tag,
			$aad,
			self::TAG_LENGTH
		);

		if (!is_string($ciphertext) || $tag === '') {
			throw new \RuntimeException('Encryption failed.');
		}

		return base64_encode($iv . $ciphertext . $tag);
	}

	private function decrypt(string $payload, string $key, string $aad = ''): string
	{
		$decoded = base64_decode($payload, true);
		if (!is_string($decoded) || strlen($decoded) < (self::IV_LENGTH + self::TAG_LENGTH)) {
			throw new \RuntimeException('Invalid encrypted payload.');
		}

		$iv = substr($decoded, 0, self::IV_LENGTH);
		$tag = substr($decoded, -self::TAG_LENGTH);
		$ciphertext = substr($decoded, self::IV_LENGTH, -self::TAG_LENGTH);

		$plaintext = openssl_decrypt(
			$ciphertext,
			'aes-256-gcm',
			$key,
			OPENSSL_RAW_DATA,
			$iv,
			$tag,
			$aad
		);

		if (!is_string($plaintext)) {
			throw new \RuntimeException('Decrypt failed.');
		}

		return $plaintext;
	}

	private function resolveKey(): string
	{
		$keyB64 = env('API_PAYLOAD_CRYPTO_KEY', env('APP_MASTER_KEY'));
		$key = is_string($keyB64) ? base64_decode($keyB64, true) : false;

		if (!is_string($key) || strlen($key) !== 32) {
			throw new \RuntimeException('Invalid key, expected base64 encoded 32-byte key.');
		}

		return $key;
	}

	private function resolveAad(Request $request): string
	{
		$aad = $request->header('X-Payload-AAD', '');
		return is_string($aad) ? $aad : '';
	}

	private function shouldHandleRequest(string $mode): bool
	{
		return $mode === 'both' || $mode === 'request';
	}

	private function shouldHandleResponse(string $mode): bool
	{
		return $mode === 'both' || $mode === 'response';
	}

	private function shouldUseEncryptedTransport(Request $request): bool
	{
		$value = $request->query('encrypted', $request->input('encrypted', $request->header('X-Encrypted')));

		if (is_bool($value)) {
			return $value;
		}

		if (is_numeric($value)) {
			return (int)$value === 1;
		}

		if (is_string($value)) {
			return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
		}

		return false;
	}
}
