<?php

	namespace App\Repositories\Crypto\Solana\Services;

	use App\Repositories\Crypto\Solana\Contracts\SolanaRpcClientInterface;
	use Illuminate\Support\Facades\Http;
	use Illuminate\Support\Facades\Log;

	class SolanaRpcClient implements SolanaRpcClientInterface
	{
		public function __construct(private string $rpcUrl, private string $commitment)
		{
		}

		/**
		 * @param string $method
		 * @param array $params
		 * @return mixed|null
		 */
	private function call(string $method, array $params)
	{
		// basic RPC tracing for production debugging (no secrets in params)
		Log::channel('crypto')->info('solana.rpc.request', [
			'method' => $method,
			'rpc_host' => parse_url($this->rpcUrl, PHP_URL_HOST),
		]);

		// backoff minimal
		$resp = Http::timeout(20)->post($this->rpcUrl, [
			'jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params
		]);
			$data = $resp->json();
			if (isset($data['error'])) throw new \RuntimeException(json_encode($data['error']));
			return $data['result'] ?? null;
		}

		/**
		 * @param string $owner
		 * @param string $mint
		 * @return array
		 */
		public function getTokenAccountsByOwnerForMint(string $owner, string $mint): array
		{
			$res = $this->call('getTokenAccountsByOwner', [
				$owner,
				['mint' => $mint],
				['encoding' => 'jsonParsed', 'commitment' => $this->commitment]
			]);
			return array_map(fn($v) => $v['pubkey'], $res['value'] ?? []);
		}

		/**
		 * @param string $address
		 * @param string|null $until
		 * @param int $limit
		 * @return array
		 */
		public function getSignaturesForAddress(string $address, ?string $until, int $limit): array
		{
			$opts = ['limit' => $limit, 'commitment' => $this->commitment];
			if ($until) $opts['until'] = $until;
			return $this->call('getSignaturesForAddress', [$address, $opts]) ?? [];
		}

		/**
		 * @param string $sig
		 * @return array|null
		 */
		public function getTransaction(string $sig): ?array
		{
			return $this->call('getTransaction', [
				$sig,
				['encoding' => 'jsonParsed', 'commitment' => $this->commitment, 'maxSupportedTransactionVersion' => 0]
			]);
		}

		/**
		 * @param array $signatures
		 * @return array
		 */
		public function getSignatureStatuses(array $signatures): array
		{
			return $this->call('getSignatureStatuses', [
					$signatures,
					['searchTransactionHistory' => true]
				]) ?? [];
		}

		public function getTokenAccountBalance(string $tokenAccount): ?array
		{
			$res = $this->call('getTokenAccountBalance', [
				$tokenAccount,
				['commitment' => $this->commitment]
			]);

			if (!isset($res['value'])) {
				return null;
			}

			$v = $res['value'];

			return [
				'amount' => (string)($v['amount'] ?? '0'),            // base units
				'decimals' => (int)($v['decimals'] ?? 0),
				'ui_amount' => (string)($v['uiAmountString'] ?? '0'),
			];
		}
	}
