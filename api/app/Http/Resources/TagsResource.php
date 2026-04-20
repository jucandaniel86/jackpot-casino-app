<?php

	namespace App\Http\Resources;

	use Illuminate\Http\Request;
	use Illuminate\Http\Resources\Json\JsonResource;

	class TagsResource extends JsonResource
	{
		/**
		 * Transform the resource into an array.
		 *
		 * @return array<string, mixed>
		 */
		public function toArray(Request $request): array
		{
			return [
				"iconCode" => $this->icon,
				"internalName" => $this->slug,
				"isDefaultTab" => false,
				"id" => "",
				"tabName" => $this->name,
			];
		}
	}