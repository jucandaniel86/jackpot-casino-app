<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTournamentRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'name' => 'required|string|max:255',
			'thumbnail' => 'nullable|string|max:500',
			'thumbnail_file' => 'nullable|image|max:5120',
			'started_at' => 'required|date',
			'ended_at' => 'required|date|after:started_at',
			'status' => 'required|in:draft,scheduled,active,finished,cancelled',
			'point_rate' => 'required|integer|min:1',
			'game_ids' => 'required|array|min:1',
			'game_ids.*' => 'required',
			'prizes' => 'nullable|array',
			'prizes.*.prize_name' => 'required|string|max:255',
			'prizes.*.prize_type' => 'required|in:rank,threshold',
			'prizes.*.rank_from' => 'nullable|integer|min:1',
			'prizes.*.rank_to' => 'nullable|integer|min:1',
			'prizes.*.min_points' => 'nullable|integer|min:0',
			'prizes.*.prize_currency' => 'nullable|string|max:20',
			'prizes.*.prize_amount' => 'required|numeric|min:0',
			'prizes.*.metadata' => 'nullable|array',
		];
	}

	public function withValidator(Validator $validator): void
	{
		$validator->after(function (Validator $validator) {
			$prizes = $this->input('prizes');
			if (!is_array($prizes)) {
				return;
			}

			foreach ($prizes as $index => $prize) {
				if (!is_array($prize)) {
					continue;
				}

				$type = $prize['prize_type'] ?? null;
				if ($type === 'rank') {
					if (empty($prize['rank_from'])) {
						$validator->errors()->add("prizes.$index.rank_from", 'rank_from is required when prize_type is rank.');
					}
				}
				if ($type === 'threshold') {
					if (!array_key_exists('min_points', $prize) || $prize['min_points'] === null || $prize['min_points'] === '') {
						$validator->errors()->add("prizes.$index.min_points", 'min_points is required when prize_type is threshold.');
					}
				}
			}
		});
	}
}
