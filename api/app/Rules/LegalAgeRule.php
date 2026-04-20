<?php

	namespace App\Rules;

	use Closure;
	use Illuminate\Contracts\Validation\ValidationRule;
	use Carbon\Carbon;

	class LegalAgeRule implements ValidationRule
	{
		public $legalAge = 18;

		/**
		 * Create a new rule instance.
		 *
		 * @return void
		 */
		public function __construct($age)
		{
			$this->legalAge = $age;
		}

		public function validate(string $attribute, mixed $value, Closure $fail): void
		{
			$formattedValue = new Carbon($value);
			$legalAge = Carbon::now()->subYears($this->legalAge);
			if ($formattedValue > $legalAge) {
				$fail('You must be at least ' . $this->legalAge . 'years old!');
			}
		}
	}