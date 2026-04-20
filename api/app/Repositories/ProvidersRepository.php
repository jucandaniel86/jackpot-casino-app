<?php

	namespace App\Repositories;

	use App\Exceptions\ApiResponseException;
	use App\Interfaces\ProvidersInterface;
	use App\Models\Providers;
	use App\Traits\QueryTrait;
	use App\Traits\UploadFilesTrait;
	use Illuminate\Database\QueryException;
	use Illuminate\Support\Str;

	class ProvidersRepository implements ProvidersInterface
	{
		use QueryTrait, UploadFilesTrait;


		public function list(array $params = []): array
		{
			return Providers::when(isset($params['search']) && strlen($params['search']) > 1, function ($query) use ($params) {
				$query->whereRaw("`name` LIKE '%{$params['search']}%'");
			})->get()->toArray();
		}


		/**
		 * @param array $params
		 * @return Providers
		 * @throws ApiResponseException
		 */
		public function save(array $params = [])
		{
			try {
				$ID = (isset($params['id']) && (int)$params['id'] > 0 ? (int)$params['id'] : 0);

				$Provider = ($ID > 0) ? Providers::find($ID) : new Providers();

				$Provider->name = $params['name'];
				$Provider->active = (int)$params['active'];
				$Provider->slug = Str::slug($params['name']);

				$path = config('casino.uploads.providers');

				if (isset($params['thumbnail_file']) && $params['thumbnail_file'] !== 'null') {
					$filePath = $path . $Provider->thumbnail;
					$thumbnail = $this->uploadThumbnail($params['thumbnail_file'], $path, $params['name'], function () use ($filePath) {
						if (@is_file(public_path($filePath))) {
							@unlink(public_path($filePath));
						}
					});
					$Provider->thumbnail = $thumbnail;
				}

				$Provider->save();
				return $Provider;
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
			$result = $this->deleteByID(Providers::class, $id, function ($model) {
				$filePath = config('casino.uploads.providers') . $model->thumbnail;

				if (@is_file(public_path($filePath))) {
					@unlink(public_path($filePath));
				}
			});
			return $result;
		}
	}