<?php

	namespace App\Repositories;

	use App\Exceptions\ApiResponseException;
	use App\Http\Resources\MenuResource;
	use App\Interfaces\MenuInterface;
	use App\Models\Menu;
	use App\Traits\QueryTrait;
	use Illuminate\Database\QueryException;
	use Illuminate\Http\Request;
	use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
	use Illuminate\Support\Str;

	class MenuRepository implements MenuInterface
	{
		use QueryTrait;

		public function items(Request $request): array
		{
			return Menu::when($request->has('position'), function ($query) use ($request) {
				$query->where('position', $request->get('position'));
			})
				->when($request->has('int_casino_id') && (string)$request->get('int_casino_id') !== "", function ($query) use ($request) {
					$query->where('int_casino_id', $request->get('int_casino_id'));
				})
				->get()->toArray();
		}

		/**
		 * @param Request $request
		 * @return array
		 */
		public function save(Request $request): array
		{
			try {
				$ID = $request->has('id') && $request->get('id') > 0 ? $request->get('id') : 0;

				$Menu = !$ID ? new Menu() : Menu::find($ID);
				$Menu->title = $request->get('title');
				$Menu->icon = $request->get('icon') ? $request->get('icon') : '';
				$Menu->action_type = $request->get('action_type');
				$Menu->item_order = 0;
				$Menu->overlay = $request->get('overlay') ? $request->get('overlay') : '';
				$Menu->page_id = $request->get('page_id') ? $request->get('page_id') : 0;
				$Menu->game_id = $request->get('game_id') ? $request->get('game_id') : 0;
				$Menu->promotion_id = $request->get('promotion_id') ? $request->get('promotion_id') : 0;
				$Menu->external_link = $request->get('external_link');
				$Menu->position = $request->get('position');
				$Menu->is_same_tab = ($request->get('is_same_tab')) ? $request->get('is_same_tab') : 0;
				$Menu->int_casino_id = $request->get('int_casino_id') ?? config('casino.defaultCasinoId');
				if (!$ID) {
					$Menu->menu_id = Str::uuid();
				}
				$Menu->save();

				return [
					'success' => true,
					'item' => $Menu
				];


			} catch (QueryException $exception) {
				activity()
					->causedBy(null)
					->withProperties([
						'message' => $exception->getMessage(),
						'line' => $exception->getLine(),
						'code' => $exception->getCode(),
						'file' => $exception->getFile()
					])
					->log(config('errors.31'));
				throw  new ApiResponseException($exception->getMessage());
			}

		}

		/**
		 * @param int $id
		 * @return array
		 */
		public function remove(int $id): array
		{
			return $this->deleteByID(Menu::class, $id);
		}

		/**
		 * @param $menuType
		 * @return AnonymousResourceCollection
		 */
		public function menu($menuType, string $casinoID = ""): AnonymousResourceCollection
		{
			$Menu = Menu::query()->where('position', '=', $menuType)
				->when($casinoID && (string)$casinoID !== "", function ($query) use ($casinoID) {
					$query->where('int_casino_id', $casinoID);
				})
				->orderBy('item_order', 'ASC')->get();
			return MenuResource::collection($Menu);
		}
	}