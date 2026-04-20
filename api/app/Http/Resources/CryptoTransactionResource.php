<?php

	namespace App\Http\Resources;

	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Repositories\Crypto\Support\Money;
	use Illuminate\Http\Resources\Json\JsonResource;

	class CryptoTransactionResource extends JsonResource
	{
		public function toArray($request)
		{
			$explorerBase = config('crypto.explorers.solana_tx', 'https://solscan.io/tx/');

			$currency = (string)($this->currency ?? '');
			$internalDecimals = CurrencyDecimals::internalForCurrency($currency);
			$uiDecimals = CurrencyDecimals::uiForCurrency($currency);

			// amount UI: prefer t.amount (dacă e setat), altfel derive din base+decimals
			$amountUi = $this->amount;
			if ($amountUi === null) {
				$amountUi = Money::baseToUi((string)$this->amount_base, $internalDecimals, $uiDecimals);
			}

			return [
				'username' => $this->username,          // poate fi null dacă nu e player wallet
				'uuid' => $this->uuid,
				'currency' => $this->currency,
				'amount' => (string)$amountUi,          // UI string
				'amount_base' => (string)$this->amount_base,
				'decimals' => $internalDecimals,
				'ui_decimals' => $uiDecimals,

				'type' => $this->type,
				'status' => $this->status,

				'txid' => $this->txid,
				'to_address' => $this->to_address,
				'from_address' => $this->from_address,

				'link' => $this->txid ? ($explorerBase . $this->txid) : null,

				'meta' => $this->meta ? (is_array($this->meta) ? $this->meta : json_decode($this->meta, true)) : null,
				'created_at' => $this->created_at,
			];
		}
	}
