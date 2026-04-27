<?php
	return [
		'game_forge' => [
			'secret' => env('GAMEFORGE_SECRET', 'IksJ3+A42gUR9l/80kZMU8r2af6oqgbkm7hvRs69TQ9KxjGGMSY3ZBSfr/1FAce/8QuzC2NBAsZ5paiEt8XatQ=='),
			'wallet_key' => env('GAMEFORGE_WALLET_KEY', 'pOpue/jj5VPwa9LkaK0/I7/6S3BWrQ4Ji0cArIA4SZaJehzo6eXLT9jxPB4bard2KGNSZNLw9juMQAuiFU3fmA=='),
			'log_channel' => 'gameforge',
			'operator_id' => 6,
			'endpoint' => env('GAMEFORGE_REAL_URL', 'https://uat.igameforge.com/gforge/cii/gfc/real'),
			'endpoint_demo' => env('GAMEFORGE_DEMO_URL', 'https://uat.igameforge.com/gforge/cii/gfc/demo'),
			'meta' => [
				'brand_id' => "jk",
				'language' => 'EN',
				'deposit_modal' => 'wallet',
				'return_modal' => 'wallet'
			]
		]
	];