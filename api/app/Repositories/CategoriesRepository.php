<?php

	namespace App\Repositories;

	use App\Exceptions\ApiResponseException;
	use App\Interfaces\CategoriesInterface;
	use App\Models\Categories;
	use App\Traits\QueryTrait;
	use Illuminate\Database\QueryException;
	use Illuminate\Support\Str;

	class CategoriesRepository implements CategoriesInterface
	{
		use QueryTrait;

		public function list(array $params = []): array
		{
			return Categories::with('descendants')->when(isset($params['search']) && strlen($params['search']) > 1, function ($query) use ($params) {
				$query->whereRaw("`name` LIKE '%{$params['search']}%'");
			})->where('parent_id', 0)->get()->toArray();
		}

		/**
		 * @param array $params
		 * @return Categories
		 * @throws ApiResponseException
		 */
		public function save(array $params = [])
		{
			$ParentID = 0;
			if (isset($params['parent_id'])) {
				if (is_array($params['parent_id'])) {
					$ParentID = $params['parent_id'][0];
				} else {
					$ParentID = $params['parent_id'];
				}
			}

			try {
				$ID = (isset($params['id']) ? (int)$params['id'] : 0);

				if (!$ID) {
					$Category = Categories::create([
						'name' => $params['name'],
						'restricted' => (int)$params['restricted'],
						'seo' => $params['seo'],
						'parent_id' => $ParentID,
						'icon' => $params['icon'],
						'slug' => Str::slug($params['name'])
					]);
					return $Category;
				}

				$Category = Categories::find($params['id']);

				if (!$Category) {
					throw new \Exception('Invalid ID');
				}

				$Category->update([
					'name' => $params['name'],
					'restricted' => (int)$params['restricted'],
					'seo' => $params['seo'],
					'parent_id' => $ParentID,
					'icon' => $params['icon']
				]);

				return $Category;
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
			$result = $this->deleteByID(Categories::class, $id);
			return $result;
		}
	}