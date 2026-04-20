<?php

	namespace App\Repositories\Integrations\Contracts;

	use App\Models\Player;
	use App\Models\Session;
	use Illuminate\Http\Request;

	interface IntegrationsInterface
	{
		public function supports(string $currency): bool;

		public function balance(Request $request): array;

		public function bet(Request $request): array;

		public function refund(Request $request): array;

		public function startGame(Session $session, Player $player, array $meta);

		public function startDemoGame(array $meta);
	}