<?php

	namespace App\Repositories;

	use App\Enums\SectionStatus;
	use App\Enums\Zones;
	use App\Exceptions\ApiResponseException;
	use App\Interfaces\SectionInterface;
	use App\Models\Pages;
	use App\Models\SectionGlobalConfig;
	use App\Models\Sections;
	use App\Models\Tag;
	use App\Traits\QueryTrait;
	use Illuminate\Database\QueryException;
	use Illuminate\Http\Request;

	class SectionRepository implements SectionInterface
	{
		use QueryTrait;

		public function getGlobalConfigByContainerType(string $container): array
		{
			$Setup = SectionGlobalConfig::query()->where('container', $container)->first();
			if (!$Setup) return [
				"resolutionConfig" => null,
				"data" => null
			];

			return [
				'resolutionConfig' => $Setup->resolution_config,
				'data' => $Setup->data
			];
		}

		public function addNewDraft(Request $request)
		{
			try {
				$Section = new Sections();
				$Section->container = $request->get('container');
				$Section->status = SectionStatus::DRAFT->value;
				$Section->zone = Zones::MAIN->value;
				$Section->resolution_config = $this->getGlobalConfigByContainerType($request->get('container'))['resolutionConfig'];
				$Section->data = ['resolutionConfig' => $this->getGlobalConfigByContainerType($request->get('container'))['resolutionConfig']];
				$Section->save();

				if ($request->has('page')) {
					$Page = Pages::query()->withCount('sections')->where('id', $request->get('page'))->first();
					$Section->page()->attach($request->get('page'), ['page_order' => $Page->sections_count + 1]);
				}

				if ($request->has('tag')) {
					$Tag = Tag::query()->withCount('sections')->where('id', $request->get('tag'))->first();

					$Section->tags()->attach($request->get('tag'), ['page_order' => $Tag->sections_count + 1]);
				}

				return $Section;
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
		 * @param Request $request
		 * @return void
		 * @throws ApiResponseException
		 */
		public function saveSectionData(Request $request)
		{
			try {
				$Section = Sections::query()->find($request->get('id'));
				if ($Section->status == SectionStatus::DRAFT->value) {
					$Section->status = SectionStatus::PUBLISHED->value;
				}
				$Section->data = $request->data;
				$Section->name = $request->name;
				$Section->save();

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
			$result = $this->deleteByID(Sections::class, $id);
			return $result;
		}

		/**
		 * @param $payload {object}
		 * page_id
		 * section_id
		 * pageOrder
		 * @return array
		 */
		public function changeSectionOrder($payload): array
		{
			if (!$payload['page_id'] || !$payload['order']) {
				throw new \Exception('Invalid Arguments');
			}

			if ($payload['tag']) {
				$Page = Tag::query()->where('id', $payload['page_id'])->first();

				if (!$Page) {
					throw new \Exception('Invalid Page');
				}

				$newOrder = [];
				foreach ($payload['order'] as $order) {
					$Page->sections()->updateExistingPivot($order['id'], ['page_order' => $order['pageOrder']]);
				}


				return ['success' => true];
			}

			$Page = Pages::query()->where('id', $payload['page_id'])->first();

			if (!$Page) {
				throw new \Exception('Invalid Page');
			}

			$newOrder = [];
			foreach ($payload['order'] as $order) {
				$Page->sections()->updateExistingPivot($order['id'], ['page_order' => $order['pageOrder']]);
			}


			return ['success' => true];
		}
	}