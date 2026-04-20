<?php

namespace App\Http\Resources;

use App\Repositories\Crypto\Support\CurrencyDecimals;
use Illuminate\Http\Resources\Json\JsonResource;

class BetResource extends JsonResource
{
	public function toArray($request): array
	{
		$currency = (string)($this->currency ?? '');
		$uiDecimals = CurrencyDecimals::uiForCurrency($currency);

		return [
			'id' => $this->id,
			'transaction_id' => $this->transaction_id,
			'session_id' => $this->session_id,
			'wallet_id' => (string)$this->wallet_id,
			'user_id' => (string)$this->user_id,
			'game_id' => (string)$this->game_id,
			'operator_transaction_id' => $this->operator_transaction_id,
			'operator_round_id' => $this->operator_round_id,
			'currency' => $currency,
			'ts' => (string)$this->ts,
			'refund_ts' => (string)$this->refund_ts,
			'balance_before' => $this->formatCurrencyValue((string)$this->balance_before, $uiDecimals),
			'balance_after' => $this->formatCurrencyValue((string)$this->balance_after, $uiDecimals),
			'stake' => $this->formatCurrencyValue((string)$this->stake, $uiDecimals),
			'payout' => $this->formatCurrencyValue((string)$this->payout, $uiDecimals),
			'refund' => $this->formatCurrencyValue((string)$this->refund, $uiDecimals),
			'refund_transaction_id' => $this->refund_transaction_id,
			'transaction_type' => $this->transaction_type,
			'round_finished' => (string)$this->round_finished,
			'when_placed' => $this->when_placed?->toISOString(),
			'int_casino_id' => $this->int_casino_id,
			'ui_decimals' => $uiDecimals,
			'game' => $this->whenLoaded('game'),
		];
	}

	private function formatCurrencyValue(string $value, int $uiDecimals): string
	{
		return bcadd($value, '0', max(0, $uiDecimals));
	}
}
