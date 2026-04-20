<?php

	namespace App\Traits;

	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\ModelNotFoundException;

	trait QueryTrait
	{

		/**
		 * @param Model $model
		 * @param  $params
		 * @param $search [ condition => true | false, query => 'string' ]
		 * @return array
		 */
		public function getFilteredList($model, array $params = [], array $search = [], $sort = [])
		{
			$start = (isset($params['start'])) ? $params['start'] : 0;
			$length = (isset($params['length']) && $params['length'] > 0) ? $params['length'] : 50;
			$offset = ($start - 1) * $length;

			$TotalQuery = $model;

			if (count($search)) {
				foreach ($search as $s) {
					$TotalQuery->when($s['condition'], function ($q) use ($s) {
						return $q->whereRaw($s['query']);
					});
				}
			}

			$Total = $TotalQuery->count();
			$ItemsQuery = $model
				->limit($length)
				->offset($offset);

			if (count($sort)) {
				$ItemsQuery->orderBy($sort['column'], $sort['direction']);
			} else {
				$ItemsQuery->orderBy('id', 'DESC');
			}

			if (count($search)) {
				foreach ($search as $s) {
					$ItemsQuery->when($s['condition'], function ($q) use ($s) {
						return $q->whereRaw($s['query']);
					});
				}
			}

			$Items = $ItemsQuery->get();

			return [
				'total' => $Total,
				'items' => $Items,
			];
		}

		public function vDatatable(string $model, array $config = []): array
		{
			#pagination
			$start = (isset($config['pagination']['start'])) ? $config['pagination']['start'] : 0;
			$length = (isset($config['pagination']['length']) && $config['pagination']['length'] > 0) ? $config['pagination']['length'] : 50;
			$offset = ($start - 1) * $length;

			#model
			$Model = new $model;

			$TotalQuery = $Model;

			if (isset($config['filter']) && count($config['filter'])) {
				foreach ($config['filter'] as $key => $val) {
					$TotalQuery->where($key, '=', $val);
				}
			}

			if (isset($config['like']) && count($config['like'])) {
				foreach ($config['like'] as $key => $val) {
					if ($val !== "") {
						$TotalQuery->where($key, 'like', '%' . $val . '%');
					}
				}
			}

			$Total = $TotalQuery->count();
			$ItemsQuery = $Model
				->limit($length)
				->offset($offset);

			if (isset($config['sort']) && count($config['sort'])) {
				$ItemsQuery->orderBy($config['sort']['column'], $config['sort']['direction']);
			} else {
				$ItemsQuery->orderBy('id', 'DESC');
			}

			if (isset($config['relations']) && count($config['relations'])) {
				$ItemsQuery->with($config['relations']);
			}

			if (isset($config['filter']) && count($config['filter'])) {
				foreach ($config['filter'] as $key => $val) {
					$ItemsQuery->where($key, '=', $val);
				}
			}

			if (isset($config['like']) && count($config['like'])) {
				foreach ($config['like'] as $key => $val) {
					if ($val !== "") {
						$ItemsQuery->where($key, 'like', '%' . $val . '%');
					}
				}
			}

			if (isset($config['count']) && count($config['count'])) {
				foreach ($config['count'] as $count) {
					$ItemsQuery->withCount($count);
				}
			}

			$Items = $ItemsQuery->get();

			return [
				'total' => $Total,
				'items' => $Items,
			];
		}

		/**
		 * @param string $model
		 * @param $ID
		 * @return array|string[]
		 */
		public function deleteByID(string $modelClass, $ID, callable $cb = null): array
		{
			$model = resolve($modelClass);
			try {
				$DModel = $model->findOrFail($ID);

				if (!is_null($cb)) {
					$cb($DModel);
				}

				$DModel->delete();

				activity()
					->performedOn($model)
					->causedBy(auth()->user())
					->withProperties(['id' => $ID, 'model' => $model::class])
					->log('Delete Item Model');

				return [
					'msg' => 'The entry was deleted successfuly!',
					'success' => true,
				];
			} catch (ModelNotFoundException $exception) {
				activity()
					->performedOn($model)
					->causedBy(auth()->user())
					->withProperties(['id' => $ID, 'model' => $model::class, 'message' => $exception->getMessage()])
					->log(config('errors.20'));

				return [
					'msg' => $exception->getMessage(),
					'success' => false,
				];
			}
		}

		/**
		 * @param Model $model
		 * @param $ID
		 * @return array|string[]
		 */
		public function softDeleteByID($model, $ID): array
		{
			try {
				$DModel = $model->findOrFail($ID);
				$DModel->DELETED = 1;
				$DModel->deleted_at = now();
				$DModel->deleted_by = auth()->user()->id;

				$DModel->save();

				return [
					'msg' => 'The entry was deleted successfuly!',
					'success' => true,
				];
			} catch (ModelNotFoundException $exception) {
				return [
					'msg' => $exception->getMessage(),
					'success' => false,
				];
			}
		}


		public function deleteByParams($model, $params): bool
		{
			if (!count($params)) return false;

			foreach ($params as $key => $val) {
				$model->where($key, $val);
			}
			$model->delete();
			return true;
		}

		public function getByID($model, $ID, $with = []): array
		{
			try {
				$DModel = $model->findOrFail($ID);

				return [
					'item' => $DModel,
					'success' => true,
					'err' => [],
				];
			} catch (ModelNotFoundException $exception) {
				return [
					'item' => NULL,
					'success' => false,
					'err' => [$exception->getMessage()],
				];
			}
		}

		public function getByParmas($model, $params)
		{

			if (!count($params)) return $model->get();

			foreach ($params as $key => $param) {
				$model->where($key, '=', $param);
			}

			return $model->get();
		}
	}