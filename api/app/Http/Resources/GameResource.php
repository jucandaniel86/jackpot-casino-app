<?php

	namespace App\Http\Resources;

	use Illuminate\Http\Request;
	use Illuminate\Http\Resources\Json\JsonResource;

	class GameResource extends JsonResource
	{
		/**
		 * Transform the resource into an array.
		 *
		 * @return array<string, mixed>
		 */
		public function toArray(Request $request): array
		{
			return [
				'imageUrl' => $this->thumbnail_url,
				'id' => $this->id,
				'name' => $this->name,
				'hasDemo' => $this->is_fun ? true : false,
				'realPlayUrl' => $this->slug,
				'demoPlayUrl' => $this->slug,
				'favorite' => $this->player_id ? true : false
			];
		}
	}