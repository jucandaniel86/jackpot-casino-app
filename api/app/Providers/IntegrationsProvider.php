<?php


	namespace App\Providers;

	use App\Repositories\Integrations\GameForge\Services\GameForgeService;
	use App\Repositories\Integrations\Support\IntegrationsRegistry;
	use Illuminate\Support\ServiceProvider;


	class IntegrationsProvider extends ServiceProvider
	{
		public function register(): void
		{
			$this->app->singleton(IntegrationsRegistry::class, function ($app) {
				return new IntegrationsRegistry(
					integrations: [
						$app->make(GameForgeService::class),
					]
				);
			});
		}
	}