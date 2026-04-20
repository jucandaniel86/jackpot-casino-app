<?php

	namespace App\Repositories\Integrations\Support;

	use App\Repositories\Integrations\Contracts\IntegrationsInterface;

	class IntegrationsRegistry
	{
		/**
		 * @param array $integrations
		 */
		public function __construct(
			private array $integrations,
		)
		{
		}

		public function tryIntegration(string $provider): ?IntegrationsInterface
		{
			foreach ($this->integrations as $p) {
				if ($p->supports($provider)) return $p;
			}
			throw new \RuntimeException("No integration for provider={$provider}");
		}
	}