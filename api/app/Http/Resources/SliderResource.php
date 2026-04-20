<?php

	namespace App\Http\Resources;

	use Illuminate\Http\Request;
	use Illuminate\Http\Resources\Json\JsonResource;

	class SliderResource extends JsonResource
	{
		/**
		 * Transform the resource into an array.
		 *
		 * @return array<string, mixed>
		 */
		public function toArray(Request $request): array
		{
			$CurrentPage = $this->page;
			return [
				'name' => $this->name,
				'contentRules' => [
					'LG' => $this->bannerUrl,
					'XL' => $this->bannerUrl,
					'MD' => $this->bannerUrl,
					'XS' => $this->bannerMobileUrl,
					'SM' => $this->bannerMobileUrl
				],
				'action' => [
					'actionType' => $this->action_type,
					'slug' => $CurrentPage ? $CurrentPage->slug : '',
					'url' => $this->url,
					'overlay' => $this->overlay
				]
			];
		}
	}