<?php

	namespace App\Repositories;

	use App\Exceptions\ApiResponseException;
	use App\Interfaces\GameInterface;
	use App\Interfaces\SliderInterface;
	use App\Models\Game;
	use App\Models\Sections;
	use App\Models\Sliders;
	use App\Traits\QueryTrait;
	use App\Traits\UploadFilesTrait;
	use Illuminate\Database\QueryException;
	use Illuminate\Support\Str;
	use Illuminate\Http\Request;

	class SliderRepository implements SliderInterface
	{
		use QueryTrait, UploadFilesTrait;

		public function list(array $params = [])
		{
			return Sliders::all();
		}

		/**
		 * @param array $params
		 * @return Sliders
		 * @throws ApiResponseException
		 */
		public function save(array $params = [])
		{
			$path = config('casino.uploads.sliders');
			try {
				$ID = (isset($params['id']) ? (int)$params['id'] : 0);

				if (!$ID) {
					$Slider = Sliders::create([
						'name' => $params['name'],
						'page_id' => (isset($params['page_id']) && $params['page_id'] != '') ? (int)$params['page_id'] : 0,
						'overlay' => (isset($params['overlay'])) ? $params['overlay'] : '',
						'url' => (isset($params['url'])) ? $params['url'] : '',
						'action_type' => $params['action_type'],
						'is_same_tab' => (isset($params['is_same_tab'])) ? (int)$params['is_same_tab'] : 0,
						'no_follow' => (isset($params['no_follow'])) ? (int)$params['no_follow'] : 0,
						'banner' => '',
						'banner_mobile' => ''
					]);

					//upload thumbnail
					if (isset($params['banner']) && $params['banner'] !== 'null') {
						$thumbnail = $this->uploadThumbnail($params['banner'], $path, $params['name']);
						$Slider->banner = $thumbnail;
						$Slider->save();
					}

					if (isset($params['banner_mobile']) && $params['banner_mobile'] !== 'null') {
						$thumbnail = $this->uploadThumbnail($params['banner_mobile'], $path, $params['name']);
						$Slider->banner_mobile = $thumbnail;
						$Slider->save();
					}


					return $Slider;
				}

				$Slider = Sliders::find($params['id']);
				if (!$Slider) {
					throw new \Exception('Invalid ID');
				}
				$Slider->update([
					'name' => $params['name'],
					'page_id' => (isset($params['page_id']) && $path['page_id'] != '') ? (int)$path['page_id'] : 0,
					'overlay' => (isset($params['overlay'])) ? $params['overlay'] : '',
					'url' => (isset($params['url'])) ? $params['url'] : '',
					'action_type' => $params['action_type'],
					'is_same_tab' => (isset($params['is_same_tab'])) ? (int)$params['is_same_tab'] : 0,
					'no_follow' => (isset($params['no_follow'])) ? (int)$params['no_follow'] : 0,
				]);

				//upload thumbnail
				if (isset($params['banner']) && $params['banner'] !== 'null') {
					$filePath = $path . $Slider->banner;
					$thumbnail = $this->uploadThumbnail($params['banner'], $path, $params['name'], function () use ($filePath) {
						if (@is_file(public_path($filePath))) {
							@unlink(public_path($filePath));
						}
					});
					$Slider->banner = $thumbnail;
					$Slider->save();
				}

				if (isset($params['banner_mobile']) && $params['banner_mobile'] !== 'null') {
					$filePath = $path . $Slider->banner_mobile;
					$thumbnail = $this->uploadThumbnail($params['banner_mobile'], $path, $params['name'], function () use ($filePath) {
						if (@is_file(public_path($filePath))) {
							@unlink(public_path($filePath));
						}
					});
					$Slider->banner_mobile = $thumbnail;
					$Slider->save();
				}

				return $Slider;
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
			return $this->deleteByID(Sliders::class, $id, function ($model) {
				$filePath = config('casino.uploads.sliders') . $model->banner;

				if (@is_file(public_path($filePath))) {
					@unlink(public_path($filePath));
				}
			});
		}
	}