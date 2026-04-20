<?php

	namespace App\Support;

	class MailSettings
	{
		public static function resolve(?string $keyOrId): array
		{
			$settings = (array)config('mailsettings', []);
			$defaultKey = $settings['default_casino_config'] ?? null;

			$key = $keyOrId ? (string)$keyOrId : null;
			$config = [];

			if ($key && isset($settings[$key]) && is_array($settings[$key])) {
				$config = $settings[$key];
			} elseif ($defaultKey && isset($settings[$defaultKey]) && is_array($settings[$defaultKey])) {
				$config = $settings[$defaultKey];
			}

			$casinoName = $config['casino_name'] ?? config('casino.name', config('app.name'));
			$loginUrl = $config['login_url'] ?? config('casino.support_url', config('app.url'));

			return [
				'url' => $config['url'] ?? $loginUrl,
				'admin_email' => $config['admin_email'] ?? null,
				'logo' => $config['logo_path'] ?? null,
				'casino_name' => $casinoName,
				'footer' => $config['footer'] ?? ($casinoName . '. All rights reserved.'),
				'login_url' => $loginUrl,
			];
		}
	}
