<?php

	namespace App\Listeners;

	use App\Events\PlayerRegistered;
	use App\Models\Player;
	use App\Notifications\WelcomePlayerNotification;
	use Illuminate\Support\Facades\Log;

	class SendWelcomeEmail
	{
		public function handle(PlayerRegistered $event): void
		{
			$player = Player::query()->find($event->playerId);
			if (!$player || !method_exists($player, 'notify')) {
				return;
			}

			try {
				$player->notify(new WelcomePlayerNotification());
			} catch (\Throwable $exception) {
				Log::warning('Welcome email failed after player registration', [
					'player_id' => $player->id,
					'email' => $player->email,
					'error' => $exception->getMessage(),
				]);
			}
		}
	}
