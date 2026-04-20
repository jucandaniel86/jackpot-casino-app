<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DecodeCasinoIdMiddleware
{
	public function handle(Request $request, Closure $next): Response
	{
		$this->normalizeCasinoId($request);

		return $next($request);
	}

	private function normalizeCasinoId(Request $request): void
	{
		$casinoId = $request->input('casino_id');
		if (is_string($casinoId) && $casinoId !== '') {
			$decodedCasinoId = $this->decodeCasinoId($casinoId);
			if ($decodedCasinoId !== null && $decodedCasinoId !== '') {
				$this->applyCasinoId($request, $decodedCasinoId);
				return;
			}

			$resolvedFromCasinoId = $this->resolvePublicCasinoId($casinoId);
			if ($resolvedFromCasinoId !== null && $resolvedFromCasinoId !== '') {
				$this->applyCasinoId($request, $resolvedFromCasinoId);
				return;
			}
		}

		$publicCasinoId = $this->extractPublicCasinoId($request);
		if ($publicCasinoId === null || $publicCasinoId === '') {
			return;
		}

		$resolvedCasinoId = $this->resolvePublicCasinoId($publicCasinoId);
		if ($resolvedCasinoId === null || $resolvedCasinoId === '') {
			return;
		}

		$this->applyCasinoId($request, $resolvedCasinoId);
	}

	private function applyCasinoId(Request $request, string $casinoId): void
	{
		$request->merge(['casino_id' => $casinoId]);
		$request->query->set('casino_id', $casinoId);
		$request->request->set('casino_id', $casinoId);
	}

	private function extractPublicCasinoId(Request $request): ?string
	{
		$raw = $request->input('public_casino_id', $request->header('X-Casino-Public-Id'));
		if (!is_string($raw)) {
			return null;
		}

		$value = trim($raw);
		if ($value === '') {
			return null;
		}

		// If prefixed as b64:<value>, decode explicitly for deterministic behavior.
		if (str_starts_with($value, 'b64:')) {
			$decoded = $this->decodeCasinoId($value);
			return is_string($decoded) && $decoded !== '' ? $decoded : null;
		}

		return $value;
	}

	private function resolvePublicCasinoId(string $publicCasinoId): ?string
	{
		$cacheKey = 'casino:public-id:' . sha1(strtolower($publicCasinoId));

		return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($publicCasinoId) {
			$query = DB::table('casinos')->select('int_casino_id');

			$query->where('uuid', $publicCasinoId)
				->orWhere('brand_id', $publicCasinoId)
				->orWhere('username', $publicCasinoId);

			if (ctype_digit($publicCasinoId)) {
				$query->orWhere('id', (int)$publicCasinoId);
			}

			$intCasinoId = $query->value('int_casino_id');

			return is_string($intCasinoId) && $intCasinoId !== '' ? $intCasinoId : null;
		});
	}

	private function decodeCasinoId(string $raw): ?string
	{
		$value = trim($raw);
		if ($value === '') {
			return null;
		}

		if (str_starts_with($value, 'b64:')) {
			$value = substr($value, 4);
		}

		if ($value === '' || !preg_match('/^[A-Za-z0-9_\-\/=+]+$/', $value)) {
			return null;
		}

		$normalized = strtr($value, '-_', '+/');
		$padding = strlen($normalized) % 4;
		if ($padding > 0) {
			$normalized .= str_repeat('=', 4 - $padding);
		}

		$decoded = base64_decode($normalized, true);
		if (!is_string($decoded)) {
			return null;
		}

		if (!mb_check_encoding($decoded, 'UTF-8')) {
			return null;
		}

		$decoded = trim($decoded);
		if ($decoded === '') {
			return null;
		}

		if (strlen($decoded) < 3 || strlen($decoded) > 128) {
			return null;
		}

		if (!preg_match('/^[A-Za-z0-9._:-]+$/', $decoded)) {
			return null;
		}

		return $decoded;
	}
}
