<?php

	namespace App\Http\Resources;

	use App\Repositories\RewardRepository;
	use Illuminate\Http\Request;
	use Illuminate\Http\Resources\Json\JsonResource;

	class PlayerResource extends JsonResource
	{
		/**
		 * Transform the resource into an array.
		 *
		 * @return array<string, mixed>
		 */
		public function toArray(Request $request): array
		{
			return [
				'id' => $this->casino_id,
				'username' => $this->username,
				'email' => $this->email,
				'created_at' => $this->created_at,
				'status' => $this->active ? "ACTIVE" : "DISABLED",
				'daily_redeem' => app(RewardRepository::class)->dailyRedeemOffer(
					$this->resource,
					(string)($this->int_casino_id ?? $request->get('casino_id') ?? '')
				),
			];
		}
	}
