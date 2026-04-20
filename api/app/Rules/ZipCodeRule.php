<?php

	namespace App\Rules;

	use Closure;
	use Illuminate\Contracts\Validation\ValidationRule;

	class ZipCodeRule implements ValidationRule
	{
		/**
		 * Run the validation rule.
		 *
		 * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
		 */
		public function validate(string $attribute, mixed $value, Closure $fail): void
		{
			if (!preg_match('/\b\d{5}\b/', $value)) {
				$fail('ZIP code must contain 5 digits!');
			}
		}
	}