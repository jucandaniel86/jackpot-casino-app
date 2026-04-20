<?php

	namespace App\Http\Requests;

	use Illuminate\Foundation\Http\FormRequest;
	use Illuminate\Validation\Rule;

	class UserRegisterRequest extends FormRequest
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
			$casinoId = $this->input('casino_id');

			return [
				'casino_id' => 'required|string',
				'email' => [
					'required',
					'email',
					Rule::unique('players')->where('int_casino_id', $casinoId),
				],
				'username' => [
					'required',
					Rule::unique('players')->where('int_casino_id', $casinoId),
				],
				'password' => 'required',
				'legalAge' => 'accepted',
			];
		}
	}