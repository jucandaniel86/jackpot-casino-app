<?php

	namespace App\Http\Requests;

	use App\Rules\LegalAgeRule;
	use App\Rules\ZipCodeRule;
	use Illuminate\Foundation\Http\FormRequest;

	class PlayerProfileRequest extends FormRequest
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
			return [
				'first_name' => 'required',
				'last_name' => 'required',
				'birthday' => ['required', 'date_format:Y-m-d', new LegalAgeRule(18)],
				'country' => 'required',
				'postal_code' => ['required', new ZipCodeRule()],
				'city' => 'required',
				'phone' => 'required',
			];
		}
	}