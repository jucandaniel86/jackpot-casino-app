<?php

	namespace App\Repositories\Crypto\Support;

	class SecretBox
	{
		public function encrypt(string $plaintext): string
		{
			$masterKeyB64 = env('APP_MASTER_KEY');
			$key = base64_decode($masterKeyB64, true);
			if ($key === false || strlen($key) !== 32) throw new \RuntimeException("Bad APP_MASTER_KEY");

			$iv = random_bytes(12);
			$tag = '';
			$ct = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
			if ($ct === false) throw new \RuntimeException("encrypt failed");

			return base64_encode($iv . $tag . $ct);
		}

		public function decrypt(string $blobB64): string
		{
			$masterKeyB64 = env('APP_MASTER_KEY');
			$key = base64_decode($masterKeyB64, true);
			if ($key === false || strlen($key) !== 32) throw new \RuntimeException("Bad APP_MASTER_KEY");

			$blob = base64_decode($blobB64, true);
			if ($blob === false || strlen($blob) < 28) throw new \RuntimeException("Bad blob");

			$iv = substr($blob, 0, 12);
			$tag = substr($blob, 12, 16);
			$ct = substr($blob, 28);

			$pt = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
			if ($pt === false) throw new \RuntimeException("decrypt failed");

			return $pt;
		}
	}