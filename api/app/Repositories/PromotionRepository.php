<?php

	namespace App\Repositories;

	use App\Enums\SectionStatus;
	use App\Exceptions\ApiResponseException;
	use App\Interfaces\GameInterface;
	use App\Interfaces\PromotionInterface;
	use App\Models\Game;
	use App\Models\Promotion;
	use App\Models\Sections;
	use App\Traits\QueryTrait;
	use App\Traits\UploadFilesTrait;
	use Illuminate\Database\QueryException;
	use Illuminate\Support\Str;
	use Illuminate\Http\Request;

	class PromotionRepository implements PromotionInterface
	{
		use QueryTrait, UploadFilesTrait;

		/**
		 * @param array $params
		 * @return array
		 */
		public function list(array $params = []): array
		{
			return Promotion::
			when(isset($params['name']) && strlen($params['name']) > 1, function ($query) use ($params) {
				$query->whereRaw("`name` LIKE '%{$params['name']}%' OR game_id LIKE '%{$params['name']}%'");
			})->get()->toArray();
		}

		/**
		 * @param array $params
		 * @return mixed
		 */
		public function savePromotionDraft(array $params = [])
		{
			$Promotion = Promotion::create([
				'title' => $params['title'],
				'slug' => Str::slug($params['title']),
				'active' => (int)$params['active'],
				'status' => SectionStatus::DRAFT->value,
			]);

			return $Promotion;
		}

		private function handleSEO($seoObject)
		{
			if (!json_decode($seoObject)) {
				return [
					'title' => '',
					'description' => '',
					'displayDescription' => '',
					'displayTitle' => '',
					'indexed' => 1,
				];
			}
			return json_decode($seoObject);
		}

		/**
		 * @param array $params
		 * @return Promotion
		 * @throws ApiResponseException
		 */
		public function save(array $params = [])
		{
			$path = config('casino.uploads.promotions');
			try {
				$ID = (int)$params['id'];

				$Promotion = Promotion::find($ID);
				if (!$Promotion) {
					throw new \Exception('Invalid ID');
				}
				$Promotion->update([
					'status' => SectionStatus::PUBLISHED->value,
					'title' => $params['title'],
					'subtitle' => (isset($params['subtitle'])) ? $params['subtitle'] : '',
					'description' => $params['description'],
					'smallDescription' => $params['smallDescription'],
					'howItWorks' => $params['howItWorks'],
					'terms' => $params['terms'],
					'primaryAction' => json_decode($params['primaryAction']),
					'seo' => $this->handleSEO($params['seo']),
					'active' => (int)$params['active'],
				]);


				//upload thumbnail
				if (isset($params['thumbnail']) && $params['thumbnail'] !== 'null') {
					$filePath = $path . $Promotion->thumbnail;
					$thumbnail = $this->uploadThumbnail($params['thumbnail'], $path, $params['title'], function () use ($filePath) {
						if (@is_file(public_path($filePath))) {
							@unlink(public_path($filePath));
						}
					});
					$Promotion->thumbnail = $thumbnail;
					$Promotion->save();
				}

				//upload banner
				if (isset($params['banner']) && $params['banner'] !== 'null') {
					$filePath = $path . $Promotion->banner;
					$banner = $this->uploadThumbnail($params['banner'], $path, $params['title'], function () use ($filePath) {
						if (@is_file(public_path($filePath))) {
							@unlink(public_path($filePath));
						}
					});
					$Promotion->banner = $banner;
					$Promotion->save();
				}
				return $Promotion;
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
			return $this->deleteByID(Promotion::class, $id, function ($model) {
				$filePath = config('casino.uploads.promotions') . $model->thumbnail;
				$banner = config('casino.uploads.promotions') . $model->banner;

				if (@is_file(public_path($filePath))) {
					@unlink(public_path($filePath));
				}

				if (@is_file(public_path($banner))) {
					@unlink(public_path($banner));
				}
			});
		}

		/**
		 * @param $ID
		 * @return array
		 */
		public function getItem($ID): array
		{
			return $this->getByID(Promotion::query(), $ID);
		}


	}