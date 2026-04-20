<?php

	namespace App\Repositories;

	use App\Exceptions\ApiResponseException;
	use App\Interfaces\IconsInterface;
	use App\Interfaces\ProvidersInterface;
	use App\Models\Icons;
	use App\Models\Providers;
	use App\Traits\QueryTrait;
	use App\Traits\UploadFilesTrait;
	use Illuminate\Database\QueryException;
	use Illuminate\Http\Request;
	use Illuminate\Support\Str;

	class IconsRepository implements IconsInterface
	{
		use QueryTrait;

		/**
		 * @return array
		 */
		public function list(): array
		{
			return Icons::all()->toArray();
		}

		/**
		 * @param Request $request
		 * @return Icons
		 * @throws ApiResponseException
		 */
		public function save(Request $request)
		{
			try {
				$Icon = ($request->has('id')) ? Icons::where('id', $request->get('id'))->first() : new Icons();
				$Icon->label = $request->get('label');
				$Icon->icon = $request->get('icon');
				$Icon->save();

				return $Icon;
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
			$result = $this->deleteByID(Icons::class, $id);
			return $result;
		}

		public function import()
		{
			$Icons = config('icons');
			$_to_insert = [];
			foreach ($Icons as $icon) {
				$_to_insert[] = [
					'label' => $icon['label'],
					'icon' => $icon['icon']
				];
			}
			Icons::insert($_to_insert);
		}
	}