<?php

	namespace App\Http\Resources;

	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use Illuminate\Http\Resources\Json\JsonResource;
	use App\Repositories\Crypto\Support\Money;

	/** @mixin \App\Repositories\Crypto\DTO\WalletView */
	class WalletResource extends JsonResource
	{
		public function toArray($request): array
		{
			$w = $this->wallet;
			$t = $this->type;
			$b = $this->balance;

			$meta = $w->meta ?? [];
			$decimals = CurrencyDecimals::internalForWallet($w);
			$uiDecimals = CurrencyDecimals::uiForWallet($w);
			$displayDecimals = $uiDecimals > 0 ? $uiDecimals : $decimals;

			$availableBase = (string)($b?->available_base ?? '0');
			$reservedBase = (string)($b?->reserved_base ?? '0');

			return [
				'wallet_id' => $w->uuid,

				'name' => $t?->name ?? $w->name,
				'symbol' => $t?->symbol,
				'code' => $t?->code,
				'purpose' => $t?->purpose ?? ($meta['purpose'] ?? 'real'),
				'is_bonus' => ($t?->purpose ?? ($meta['purpose'] ?? 'real')) === 'bonus',
				'icon' => $t?->icon,
				'is_fiat' => (int)($t?->is_fiat ?? 0),
					'precision' => $displayDecimals,
				'minAmount' => $t->min_amount,

				'currency_id' => $w->currency_id,
				'currency' => $w->currency_code, // PEP
				'network' => $w->network,

				'owner_address' => $meta['owner_address'] ?? null,
				'token_mint' => $meta['token_mint'] ?? null,

				'canWithdraw' => (bool)($this->canWithdraw ?? true),
				// Keep *_base as true base units from wallet_balances.
				'available_base' => $availableBase,
				'reserved_base' => $reservedBase,
				'available_ui' => Money::baseToUi($availableBase, $decimals, $displayDecimals),
				'reserved_ui' => Money::baseToUi($reservedBase, $decimals, $displayDecimals),
				'decimals' => $decimals,
				'ui_decimals' => $uiDecimals,

					'available' => Money::baseToUi($availableBase, $decimals, $displayDecimals),
					'reserved' => Money::baseToUi($reservedBase, $decimals, $displayDecimals),
				];
			}
		}
