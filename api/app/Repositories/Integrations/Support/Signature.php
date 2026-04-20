<?php

	namespace App\Repositories\Integrations\Support;

	class Signature
	{
		/**
		 * @param string $base64Key
		 * @param string $jsonData
		 * @return string
		 */
		public static function createSignatureDigibet(string $base64Key, string $jsonData)
		{
			// Decode base64 key
			$key = base64_decode($base64Key);
			// Compute HMAC-SHA256 and return base64 result
			$hash = hash_hmac('sha256', $jsonData, $key, true);
			return base64_encode($hash);
		}


	}