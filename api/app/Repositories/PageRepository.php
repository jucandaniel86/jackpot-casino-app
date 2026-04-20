<?php

	namespace App\Repositories;

	use App\Exceptions\ApiResponseException;
	use App\Interfaces\PageInterface;
	use App\Models\Categories;
	use App\Models\Pages;
	use App\Traits\QueryTrait;
	use Illuminate\Database\QueryException;
	use Illuminate\Support\Str;

	class PageRepository implements PageInterface
	{
		use QueryTrait;

		const DEFAULT_LAYOUT = 'default';

		/**
		 * @param array $params
		 * @return array
		 */
		public function list(array $params = []): array
		{
			return Pages::when(isset($params['search']) && strlen($params['search']) > 1, function ($query) use ($params) {
				$query->whereRaw("`name` LIKE '%{$params['search']}%'");
			})
				->when(isset($params['int_casino_id']) && (string)$params['int_casino_id'] !== "", function ($query) use ($params) {
					$query->where('int_casino_id', $params['int_casino_id']);
				})
				->get()
				->toArray();
		}

		/**
		 * @param array $params
		 * @return Pages
		 * @throws ApiResponseException
		 */
		public function save(array $params = [])
		{
			try {
				$ID = (isset($params['id']) ? (int)$params['id'] : 0);

				if (!$ID) {
					$Page = Pages::create([
						'name' => $params['name'],
						'restricted' => (isset($params['restricted'])) ? (int)$params['restricted'] : 0,
						'seo' => isset($params['seo']) ? $params['seo'] : null,
						'slug' => Str::slug($params['name']),
						'layout' => self::DEFAULT_LAYOUT,
						'int_casino_id' => $params['int_casino_id'] ?? config('casino.defaultCasinoId')
					]);
					return $Page;
				}

				$Page = Pages::find($params['id']);
				if (!$Page) {
					throw new \Exception('Invalid ID');
				}
				$Page->update([
					'name' => $params['name'],
					'restricted' => (int)$params['restricted'],
					'seo' => $params['seo'],
					'layout' => self::DEFAULT_LAYOUT
				]);

				return $Page;
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
		 * @param $id
		 * @return string[]
		 */
		public function remove($id)
		{
			$result = $this->deleteByID(Pages::class, $id);
			return $result;
		}

		/**
		 * @param $id
		 * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
		 */
		public function getPage($id)
		{
			return Pages::query()->where('id', $id)->with(['sections' => function ($query) {
				$query->orderByRaw('page_sections.page_order ASC');
			}])->first();
		}
	}