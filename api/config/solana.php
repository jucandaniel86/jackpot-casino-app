<?php
	return [
		'rpc_url' => env('SOLANA_RPC_URL', 'https://api.mainnet-beta.solana.com'),
		'commitment' => env('SOLANA_COMMITMENT', 'confirmed'),
		'node_bin' => env('SOLANA_NODE_BIN', 'node'),
		'scan' => [
			'signature_limit' => (int)env('SOLANA_SCAN_SIG_LIMIT', 50),
		],
		'treasury' => [
			'owner_address' => env('SOLANA_TREASURY_OWNER'),     // public key base58
			'secret_enc' => env('SOLANA_TREASURY_SECRET_ENC') // encrypted 64 bytes
		],
	];
