<?php

	return [
		'name' => env('CASINO_NAME', 'Joci.RO'),
		'email' => env('CASINO_EMAIL', 'contact-casino@codebuilders.ro'),
		'logo_url' => env('CASINO_LOGO_URL', rtrim(env('APP_URL', ''), '/') . '/logo.svg'),
		'support_url' => env('CASINO_SUPPORT_URL', rtrim(env('APP_URL', ''), '/')),
		'uploads' => [
			'games' => '/uploads/games/',
			'home' => '/uploads/home/',
			'logos' => '/uploads/logos/',
			'promotions' => '/uploads/promotions/',
			'providers' => '/uploads/providers/',
			'sliders' => '/uploads/sliders/'
		],
		'demoCurrency' => 'FUN',
		'defaultCasinoId' => env('DEFAULT_CASINO_ID', 'gf-coin-casino')
	];