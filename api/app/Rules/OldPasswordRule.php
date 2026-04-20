<?php

	namespace App\Rules;

	use Closure;
	use Illuminate\Contracts\Validation\ValidationRule;
	use Illuminate\Support\Facades\Hash;

	class OldPasswordRule implements ValidationRule
	{
		/**
		 * Run the validation rule.
		 *
		 * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
		 */
		public function validate(string $attribute, mixed $value, Closure $fail): void
		{
			if (!Hash::check($value, auth('casino')->user()->password)) {
				$fail('The old password you entered is incorrect. ');
			}
		}
	}