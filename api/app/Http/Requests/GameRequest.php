<?php

	namespace App\Http\Requests;

	use http\Env\Request;
	use Illuminate\Foundation\Http\FormRequest;
	use Illuminate\Support\Fluent;
	use  Illuminate\Validation\Rule;

	class GameRequest extends FormRequest
	{
		/**
		 * Determine if the user is authorized to make this request.
		 */
		public function authorize(): bool
		{
			return true;
		}

		/**
		 * Get the validation rules that apply to the request.
		 *
		 * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
		 */
		public function rules(): array
		{
			$rules = [
				'name' => ['required'],
				'game_id' => 'required'
			];

			if ((int)$this->input('id') === 0) {
				$rules['name'] = ['required', 'unique:games'];
			}

			return $rules;
		}
	}