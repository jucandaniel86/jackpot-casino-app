<?php

namespace App\Repositories;

use App\Models\Bundle;
use App\Traits\UploadFilesTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Bundles
{
	use UploadFilesTrait;

	/**
	 * @param array{
	 *   is_active?: bool|string|int|null,
	 *   is_featured?: bool|string|int|null,
	 *   is_popular?: bool|string|int|null,
	 *   is_available_now?: bool|string|int|null,
	 *   search?: string|null,
	 *   slug?: string|null,
	 *   price_currency?: string|null,
	 *   sort_by?: string|null,
	 *   sort_direction?: string|null,
	 *   per_page?: int|null
	 * } $filters
	 */
	public function list(array $filters = []): LengthAwarePaginator
	{
		$now = now();

		$query = Bundle::query();

		foreach (['is_active', 'is_featured', 'is_popular'] as $flag) {
			if (!array_key_exists($flag, $filters) || $filters[$flag] === null) {
				continue;
			}

			$value = filter_var($filters[$flag], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
			if ($value === null) {
				continue;
			}

			$query->where($flag, $value);
		}

		if (!empty($filters['slug'])) {
			$query->where('slug', (string)$filters['slug']);
		}

		if (!empty($filters['price_currency'])) {
			$query->where('price_currency', (string)$filters['price_currency']);
		}

		if (array_key_exists('is_available_now', $filters)) {
			$isAvailableNow = filter_var($filters['is_available_now'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
			if ($isAvailableNow === true) {
				$query
					->where('is_active', true)
					->where(function ($q) use ($now) {
						$q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
					})
					->where(function ($q) use ($now) {
						$q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
					});
			}
		}

		if (!empty($filters['search'])) {
			$search = (string)$filters['search'];
			$query->where(function ($q) use ($search) {
				$q->where('name', 'like', '%' . $search . '%')
					->orWhere('slug', 'like', '%' . $search . '%')
					->orWhere('short_description', 'like', '%' . $search . '%')
					->orWhere('description', 'like', '%' . $search . '%')
					->orWhere('badge_text', 'like', '%' . $search . '%')
					->orWhere('ribbon_text', 'like', '%' . $search . '%');
			});
		}

		$allowedSortBy = ['name', 'slug', 'price_amount', 'sort_order', 'created_at', 'starts_at', 'ends_at'];
		$sortBy = (string)($filters['sort_by'] ?? '');
		$sortDirection = strtolower((string)($filters['sort_direction'] ?? '')) === 'asc' ? 'asc' : 'desc';

		if ($sortBy !== '' && in_array($sortBy, $allowedSortBy, true)) {
			$query->orderBy($sortBy, $sortDirection)->orderBy('created_at', 'desc');
		} else {
			$query->orderBy('sort_order', 'asc')->orderBy('created_at', 'desc');
		}

		$perPage = (int)($filters['per_page'] ?? 20);
		if ($perPage <= 0) {
			$perPage = 20;
		}
		if ($perPage > 100) {
			$perPage = 100;
		}

		return $query->paginate($perPage);
	}

	public function find(string $id): Bundle
	{
		$bundle = Bundle::query()->find($id);

		if (!$bundle) {
			throw (new ModelNotFoundException())->setModel(Bundle::class, [$id]);
		}

		return $bundle;
	}

	/**
	 * @param array{
	 *   name: string,
	 *   slug: string,
	 *   short_description?: string|null,
	 *   description?: string|null,
	 *   price_amount: string|int|float,
	 *   price_currency: string,
	 *   gc_amount?: string|int|float|null,
	 *   coin_amount?: string|int|float|null,
	 *   thumbnail?: string|null,
	 *   thumbnail_file?: \Illuminate\Http\UploadedFile|null,
	 *   icon?: string|null,
	 *   badge_text?: string|null,
	 *   badge_color?: string|null,
	 *   background_color?: string|null,
	 *   text_color?: string|null,
	 *   ribbon_text?: string|null,
	 *   image_url?: string|null,
	 *   is_active?: bool|null,
	 *   is_featured?: bool|null,
	 *   is_popular?: bool|null,
	 *   sort_order?: int|null,
	 *   metadata?: array|null,
	 *   starts_at?: string|null,
	 *   ends_at?: string|null
	 * } $data
	 */
	public function create(array $data): Bundle
	{
		$payload = $this->normalizePayload($data);

		$bundle = Bundle::query()->create($payload);
		$this->uploadBundleThumbnail($bundle, $data);

		return $this->find((string)$bundle->id);
	}

	/**
	 * @param array{
	 *   name: string,
	 *   slug: string,
	 *   short_description?: string|null,
	 *   description?: string|null,
	 *   price_amount: string|int|float,
	 *   price_currency: string,
	 *   gc_amount?: string|int|float|null,
	 *   coin_amount?: string|int|float|null,
	 *   thumbnail?: string|null,
	 *   thumbnail_file?: \Illuminate\Http\UploadedFile|null,
	 *   icon?: string|null,
	 *   badge_text?: string|null,
	 *   badge_color?: string|null,
	 *   background_color?: string|null,
	 *   text_color?: string|null,
	 *   ribbon_text?: string|null,
	 *   image_url?: string|null,
	 *   is_active?: bool|null,
	 *   is_featured?: bool|null,
	 *   is_popular?: bool|null,
	 *   sort_order?: int|null,
	 *   metadata?: array|null,
	 *   starts_at?: string|null,
	 *   ends_at?: string|null
	 * } $data
	 */
	public function update(string $id, array $data): Bundle
	{
		$bundle = $this->find($id);

		$bundle->fill($this->normalizePayload($data));
		$bundle->save();
		$this->uploadBundleThumbnail($bundle, $data);

		return $this->find($id);
	}

	public function delete(string $id): void
	{
		$bundle = $this->find($id);
		if ($this->isStoredThumbnail($bundle->thumbnail)) {
			$this->deleteFile($this->uploadPath() . $bundle->thumbnail);
		}
		$bundle->delete();
	}

	/**
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	private function normalizePayload(array $data): array
	{
		$payload = Arr::only($data, [
			'name',
			'slug',
			'short_description',
			'description',
			'price_amount',
			'price_currency',
			'gc_amount',
			'coin_amount',
			'thumbnail',
			'icon',
			'label',
			'subtitle',
			'cta_text',
			'badge_text',
			'badge_color',
			'border_color',
			'accent_color',
			'background_color',
			'text_color',
			'ribbon_text',
			'tag',
			'tag_color',
			'image_url',
			'is_active',
			'is_featured',
			'is_popular',
			'sort_order',
			'metadata',
			'starts_at',
			'ends_at',
		]);

		foreach (['price_amount', 'gc_amount', 'coin_amount'] as $amountField) {
			if (array_key_exists($amountField, $payload) && $payload[$amountField] === null) {
				$payload[$amountField] = 0;
			}
		}

		foreach (['is_active', 'is_featured', 'is_popular'] as $flag) {
			if (!array_key_exists($flag, $payload) || $payload[$flag] === null) {
				unset($payload[$flag]);
				continue;
			}

			$value = filter_var($payload[$flag], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
			if ($value === null) {
				unset($payload[$flag]);
				continue;
			}
			$payload[$flag] = $value;
		}

		if (array_key_exists('sort_order', $payload) && $payload['sort_order'] !== null) {
			$payload['sort_order'] = (int)$payload['sort_order'];
		}

		return $payload;
	}

	private function uploadBundleThumbnail(Bundle $bundle, array $data): void
	{
		if (
			!isset($data['thumbnail_file']) ||
			$data['thumbnail_file'] === 'null' ||
			$data['thumbnail_file'] === null
		) {
			return;
		}

		$oldThumbnail = $bundle->thumbnail;
		$thumbnail = $this->uploadThumbnail(
			$data['thumbnail_file'],
			$this->uploadPath(),
			$bundle->name,
			function () use ($oldThumbnail) {
				if ($this->isStoredThumbnail($oldThumbnail)) {
					$this->deleteFile($this->uploadPath() . $oldThumbnail);
				}
			}
		);

		$bundle->thumbnail = $thumbnail;
		$bundle->save();
	}

	private function uploadPath(): string
	{
		return config('casino.uploads.bundles', '/uploads/bundles/');
	}

	private function isStoredThumbnail(?string $thumbnail): bool
	{
		return (bool)$thumbnail && !Str::startsWith($thumbnail, ['http://', 'https://', '/']);
	}
}
