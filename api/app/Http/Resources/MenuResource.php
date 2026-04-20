<?php

	namespace App\Http\Resources;

	use App\Enums\LinkActionTypes;
	use Illuminate\Http\Request;
	use Illuminate\Http\Resources\Json\JsonResource;

	class MenuResource extends JsonResource
	{
		/**
		 * Transform the resource into an array.
		 *
		 * @return array<string, mixed>
		 */
		public function toArray(Request $request): array
		{
			$return = [];
			$return['title'] = $this->title;
			$return['actionType'] = $this->action_type;
			$return['icon'] = $this->icon;
			$return['id'] = $this->menu_id;
			$return['isSameTab'] = $this->is_same_tab;

			switch ($this->action_type) {
				case LinkActionTypes::OPEN_EXTERNAL_PAGE->value:
					{
						$return['slug'] = $this->external_link;
					}
					break;
				case LinkActionTypes::OPEN_OVERLAY->value:
					{
						$return['slug'] = $this->overlay;
					}
					break;
				case LinkActionTypes::OPEN_INTERNAL_PAGE->value:
					{
						if ($this->page_id) {
							$return['slug'] = '/' . ($this->page ? $this->page->slug : '');
						} else if ($this->game_id) {
							$return['slug'] = '/game/' . ($this->game ? $this->game->slug : '');
						} else if ($this->promotion_id) {
							$return['slug'] = '/promotions/' . ($this->promotion ? $this->promotion->slug : '');
						}
					}
					break;
			}
			return $return;
		}
	}