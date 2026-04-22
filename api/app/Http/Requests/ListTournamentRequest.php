<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListTournamentRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'status' => 'nullable|string|in:draft,scheduled,active,finished,cancelled',
			'is_active' => 'nullable|boolean',
			'started_from' => 'nullable|date',
			'started_to' => 'nullable|date',
			'ended_from' => 'nullable|date',
			'ended_to' => 'nullable|date',
			'search' => 'nullable|string|max:255',
			'game_id' => 'nullable',
			'per_page' => 'nullable|integer|min:1|max:100',
			'sort_by' => 'nullable|in:name,started_at,ended_at,status,created_at',
			'sort_direction' => 'nullable|in:asc,desc',
		];
	}
}

