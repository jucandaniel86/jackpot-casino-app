<?php

	namespace App\Providers;

	use Illuminate\Support\ServiceProvider;

	class MarketingServiceProvider extends ServiceProvider
	{
		/**
		 * Register services.
		 */
		public function register(): void
		{
			$this->app->bind(
				\App\Marketing\Contracts\MarketingServiceInterface::class,
				\App\Marketing\Services\MarketingService::class
			);
		}

		/**
		 * Bootstrap services.
		 */
		public function boot(): void
		{
			//
		}
	}