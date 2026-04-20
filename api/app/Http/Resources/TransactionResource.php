<?php

	namespace App\Http\Resources;

	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Repositories\Crypto\Support\Money;
	use Illuminate\Http\Resources\Json\JsonResource;

	/** @mixin \App\Models\Transaction */
	class TransactionResource extends JsonResource
	{
		public function toArray($request): array
		{
			$currency = (string)($this->currency ?? $this->currency_code ?? '');
			$decimals = CurrencyDecimals::internalForCurrency($currency);
			$uiDecimals = CurrencyDecimals::uiForCurrency($currency);
			$amountBase = (string)($this->amount_base ?? '0');

			return [
				'id' => $this->id,
				'uuid' => $this->uuid,
				'type' => $this->type,           // deposit|withdraw
				'status' => $this->status,       // pending|confirmed|failed
				'currency' => $this->currency_code,
				'amount_base' => $amountBase,
				'amount' => Money::baseToUi($amountBase, $decimals, $uiDecimals),
				'decimals' => $decimals,
				'ui_decimals' => $uiDecimals,

				'txid' => $this->txid,
				'to_address' => $this->to_address, // relevant la withdraw
				'meta' => $this->meta,

				'created_at' => $this->created_at?->toISOString(),
			];
		}
	}
