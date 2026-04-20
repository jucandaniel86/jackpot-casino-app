<?php

	namespace App\Repositories;

	use App\Exceptions\ApiResponseException;
	use App\Interfaces\TagsInterface;
	use App\Models\Tag;
	use App\Traits\QueryTrait;
	use Illuminate\Database\QueryException;
	use Illuminate\Support\Str;

	class TagsRepository implements TagsInterface
	{
		use QueryTrait;

		/**
		 * @param array $params
		 * @return array
		 */
		public function list(array $params = []): array
		{
			return Tag::query()->when(isset($params['search']) && strlen($params['search']) > 1, function ($query) use ($params) {
				$query->whereRaw("`name` LIKE '%{$params['search']}%'");
			})
				->selectRaw('tags.id, tags.name, pages.name as pageName')
				->leftJoin('pages', 'tags.page_id', '=', 'pages.id')->get()->toArray();
		}

		/**
		 * @param array $params
		 * @return Tag
		 * @throws ApiResponseException
		 */
		public function save(array $params = [])
		{
			try {
				$ID = (isset($params['id']) ? (int)$params['id'] : 0);

				if (!$ID) {
					$TAG = Tag::create([
						'name' => $params['name'],
						'slug' => Str::slug($params['name']),
						'icon' => (isset($params['icon'])) ? $params['icon'] : '',
						'page_id' => (isset($params['page_id'])) ? $params['page_id'] : 0,
						'active' => 1,
					]);
					return $TAG;
				}

				$TAG = Tag::find($params['id']);
				if (!$TAG) {
					throw new \Exception('Invalid ID');
				}
				$TAG->update([
					'name' => $params['name'],
					'slug' => Str::slug($params['name']),
					'icon' => $params['icon'],
					'page_id' => (isset($params['page_id'])) ? $params['page_id'] : 0,
				]);

				return $TAG;
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
			$result = $this->deleteByID(Tag::class, $id);
			return $result;
		}

		/**
		 * @param $id
		 * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
		 */
		public function getTag($id)
		{
			return Tag::query()->where('id', $id)->with(['sections' => function ($query) {
				$query->orderByRaw('tags_sections.page_order ASC');
			}])->first();
		}
	}