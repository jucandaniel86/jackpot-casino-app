<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListBundleRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'is_active' => 'nullable|boolean',
			'is_featured' => 'nullable|boolean',
			'is_popular' => 'nullable|boolean',
			'is_available_now' => 'nullable|boolean',
			'search' => 'nullable|string|max:255',
			'slug' => 'nullable|string|max:255',
			'price_currency' => 'nullable|string|max:10',
			'per_page' => 'nullable|integer|min:1|max:100',
			'sort_by' => 'nullable|in:name,slug,price_amount,sort_order,created_at,starts_at,ends_at',
			'sort_direction' => 'nullable|in:asc,desc',
		];
	}
}

