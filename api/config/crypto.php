<?php
	return [
		'explorers' => [
			'solana_tx' => env('SOLANA_TX_EXPLORER', 'https://solscan.io/tx/'), // + {txid}
		],
		'currencies' => [
			'SOLANA:PEP' => [
				'chain' => 'solana',
				'mint' => env('SOLANA_MINT_PEP', 'DdEgvcQxVP4dPHhPJJb5KqeYoZcEzVMd7PvXtB9Rpump'),
				'internal_decimals' => (int)env('SOLANA_PEP_INTERNAL_DECIMALS', 9),
				'ui_decimals' => (int)env('SOLANA_PEP_UI_DECIMALS', env('SOLANA_PEP_INTERNAL_DECIMALS', 9)),
				'decimals' => (int)env('SOLANA_PEP_INTERNAL_DECIMALS', 9), // legacy alias
			],
		],
		'rpc' => [
			'solana' => env('SOLANA_RPC_URL', 'https://api.mainnet-beta.solana.com'),
		],
		'defaultCurrency' => 'SOLANA:PEP',
		'treasury_wallet_ids' => [
			// currency_id => wallet_id
			'PEP' => env('TREASURY_WALLET_ID_SOLANA_PEP'),
		],
		'fx' => [
			'provider' => env('FX_PROVIDER', 'coingecko'),
			'cache_ttl_seconds' => (int)env('FX_CACHE_TTL', 60), // 60s e ok
			'coingecko' => [
				'base_url' => env('COINGECKO_BASE_URL', 'https://api.coingecko.com/api/v3'),
				'api_key' => env('COINGECKO_API_KEY'), // optional
			],
			// template pt link-uri, dacă vrei și în UI
			'explorer' => [
				'solana_tx' => env('SOLANA_TX_EXPLORER', 'https://solscan.io/tx/{txid}'),
			],
		],
	];
