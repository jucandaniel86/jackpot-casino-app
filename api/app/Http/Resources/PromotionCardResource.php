<?php

	namespace App\Http\Resources;

	use Illuminate\Http\Request;
	use Illuminate\Http\Resources\Json\JsonResource;

	class PromotionCardResource extends JsonResource
	{
		/**
		 * Transform the resource into an array.
		 *
		 * @return array<string, mixed>
		 */
		public function toArray(Request $request): array
		{
			return [
				'ctaText' => isset($this->primaryAction->title) ? $this->primaryAction->title : 'Play Now',
				'description' => '<p>' . $this->smallDescription . '</p>',
				'primaryAction' => $this->primaryAction,
				'image' => $this->thumbnailUrl,
				'readMoreSlug' => '/promotions/' . $this->slug,
				'title' => '<h2>' . $this->title . '</h2>',
				'secondaryAction' => [
					'color' => 'grey',
					'title' => 'Read More',
					'action' => [
						'type' => 'OPEN_INTERNAL_PAGE',
						'slug' => '/promotions/' . $this->slug
					]
				]
			];
		}
	}