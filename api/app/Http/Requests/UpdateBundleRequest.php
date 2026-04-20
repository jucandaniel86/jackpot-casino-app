<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateBundleRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		$id = (string)$this->route('id');

		return [
			'name' => 'required|string|max:255',
			'slug' => [
				'required',
				'string',
				'max:255',
				'alpha_dash',
				Rule::unique('bundles', 'slug')->ignore($id),
			],
			'short_description' => 'nullable|string|max:500',
			'description' => 'nullable|string',

			'price_amount' => 'required|numeric|min:0',
			'price_currency' => 'required|string|max:10',
			'gc_amount' => 'nullable|numeric|min:0',
			'coin_amount' => 'nullable|numeric|min:0',

			'thumbnail' => 'nullable|string|max:500',
			'icon' => 'nullable|string|max:500',
			'label' => 'nullable|string|max:255',
			'subtitle' => 'nullable|string|max:255',
			'cta_text' => 'nullable|string|max:100',
			'badge_text' => 'nullable|string|max:100',
			'badge_color' => 'nullable|string|max:50',
			'border_color' => 'nullable|string|max:50',
			'accent_color' => 'nullable|string|max:50',
			'background_color' => 'nullable|string|max:50',
			'text_color' => 'nullable|string|max:50',
			'ribbon_text' => 'nullable|string|max:100',
			'tag' => 'nullable|string|max:100',
			'tag_color' => 'nullable|string|max:50',
			'image_url' => 'nullable|string|max:500',

			'is_active' => 'nullable|boolean',
			'is_featured' => 'nullable|boolean',
			'is_popular' => 'nullable|boolean',
			'sort_order' => 'nullable|integer|min:0',

			'metadata' => 'nullable|array',
			'starts_at' => 'nullable|date',
			'ends_at' => 'nullable|date|after:starts_at',
		];
	}

	public function withValidator(Validator $validator): void
	{
		$validator->after(function (Validator $validator) {
			$gc = $this->input('gc_amount');
			$coin = $this->input('coin_amount');

			$gcValue = is_numeric($gc) ? (float)$gc : 0.0;
			$coinValue = is_numeric($coin) ? (float)$coin : 0.0;

			if ($gcValue <= 0 && $coinValue <= 0) {
				$message = 'At least one of gc_amount or coin_amount must be greater than 0.';
				$validator->errors()->add('gc_amount', $message);
				$validator->errors()->add('coin_amount', $message);
			}
		});
	}
}
