<?php

	namespace App\Repositories;

	use App\Exceptions\ApiResponseException;
	use App\Interfaces\WalletInterface;
	use App\Models\Player;
	use App\Models\Wallet;
	use App\Models\WalletType;
	use App\Traits\QueryTrait;
	use Illuminate\Database\QueryException;
	use Illuminate\Support\Str;

	class WalletRepository implements WalletInterface
	{
		use QueryTrait;

		/**
		 * @param array $params
		 * @return Wallet
		 * @throws ApiResponseException
		 */
		public function save(array $params = [])
		{
			try {
				$ID = (isset($params['id']) ? (int)$params['id'] : 0);

				if (!$ID) {
					$WalletType = WalletType::create([
						'name' => $params['name'],
						'code' => $params['code'],
						'currency_id' => $params['currency_id'] ?? null,
						'currency_code' => $params['currency_code'] ?? ($params['code'] ?? null),
						'network' => $params['network'] ?? null,
						'symbol' => isset($params['symbol']) ? $params['symbol'] : '',
						'is_fiat' => intval($params['is_fiat']),
						'active' => intval($params['active']),
						'purpose' => $params['purpose'] ?? 'real',
						'supports_tag' => intval($params['supports_tag']),
						'min_amount' => isset($params['min_amount']) ? $params['min_amount'] : 0,
						'wallet_uuid' => Str::uuid(),
						'icon' => isset($params['icon']) ? $params['icon'] : '',
						'precision' => isset($params['precision']) ? $params['precision'] : 0,
						'network_data' => isset($params['network_data']) ? $params['network_data'] : [],
					]);
					return $WalletType;
				}

				$WalletType = WalletType::find($params['id']);

				if (!$WalletType) {
					throw new \Exception('Invalid ID');
				}

				$WalletType->update([
					'name' => $params['name'],
					'code' => $params['code'],
					'currency_id' => $params['currency_id'] ?? null,
					'currency_code' => $params['currency_code'] ?? ($params['code'] ?? null),
					'network' => $params['network'] ?? null,
					'symbol' => isset($params['symbol']) ? $params['symbol'] : '',
					'is_fiat' => intval($params['is_fiat']),
					'active' => intval($params['active']),
					'purpose' => $params['purpose'] ?? ($WalletType->purpose ?? 'real'),
					'supports_tag' => intval($params['supports_tag']),
					'min_amount' => isset($params['min_amount']) ? $params['min_amount'] : 0,
					'wallet_uuid' => Str::uuid(),
					'icon' => isset($params['icon']) ? $params['icon'] : '',
					'precision' => isset($params['precision']) ? $params['precision'] : 0,
					'network_data' => isset($params['network_data']) ? $params['network_data'] : [],
				]);

				return $WalletType;
			} catch (QueryException $exception) {
				activity()
					->causedBy(null)
					->withProperties([
						'message' => $exception->getMessage(),
						'line' => $exception->getLine(),
						'code' => $exception->getCode(),
						'file' => $exception->getFile()
					])
					->log(config('errors.31'));
				throw  new ApiResponseException($exception->getMessage());
			}
		}

		/**
		 * @param $id
		 * @return string[]
		 */
		public function remove($id)
		{
			$result = $this->deleteByID(WalletType::class, $id);
			return $result;
		}

		/**
		 * @param array $params
		 * @return array
		 */
		public function list(array $params = []): array
		{
			return WalletType::when(isset($params['search']) && strlen($params['search']) > 1, function ($query) use ($params) {
				$query->whereRaw('code LIKE "%' . $params['search'] . '%" OR name LIKE "%' . $params['search'] . '%"');
			})
				->when(isset($params['active']) && (int)$params['active'] === 1, function ($query) {
					$query->where('active', 1);
				})
				->get()
				->toArray();
		}

		public function currencies(): array
		{
			return WalletType::query()
				->where('active', 1)
				->selectRaw('currency_code')
				->pluck('currency_code')->toArray();
		}

		/**
		 * @return array
		 */
		public function createUserWallets(): array
		{
			$walletTypes = WalletType::query()
				->where('active', 1)
				->get();
			$Players = Player::all();
			$i = 0;
			foreach ($Players as $player) {
				$existingTypeIds = $player->wallets()
					->pluck('wallet_type_id')
					->map(fn($id) => (int)$id)
					->all();

				foreach ($walletTypes as $walletType) {
					$typeId = (int)$walletType->id;
					if (!in_array($typeId, $existingTypeIds, true)) {
						$i++;
						$wallet = $player->wallets()->firstOrCreate([
							'wallet_type_id' => $typeId,
						], [
							'currency' => $walletType->currency_id,
							'currency_id' => $walletType->currency_id,
							'currency_code' => $walletType->currency_code ?: $walletType->code,
							'network' => $walletType->network,
							'balance' => ($walletType->purpose ?? 'real') === 'real' && $walletType->code === 'JC' ? 5000 : 0,
							'wallet_type_id' => $typeId,
							'uuid' => Str::uuid(),
							'name' => $player->fixed_id . '_' . ($walletType->currency_code ?: $walletType->code) . '_' . ($walletType->purpose ?? 'real'),
							'meta' => ['purpose' => $walletType->purpose ?? 'real'],
						]);
						if ($wallet->wasRecentlyCreated) {
							$existingTypeIds[] = $typeId;
						} else {
							$i--;
						}
					}
				}
			}

			return [
				'newWallets' => $i
			];
		}
	}
