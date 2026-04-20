<?php

	namespace App\Listeners;

	use App\Events\WithdrawRequested;
	use App\Models\WithdrawRequest;
	use App\Notifications\WithdrawRequestedNotification;
	use Illuminate\Support\Facades\Log;

	class SendWithdrawRequestedEmail
	{
		public function handle(WithdrawRequested $event): void
		{
			$request = WithdrawRequest::query()
				->where('uuid', $event->withdrawRequestUuid)
				->first();

			if (!$request) {
				Log::warning('Withdraw request email skipped: request not found', [
					'uuid' => $event->withdrawRequestUuid,
				]);
				return;
			}

			$player = $request->player;
			if (!$player || !method_exists($player, 'notify')) {
				Log::warning('Withdraw request email skipped: player missing or not notifiable', [
					'uuid' => $event->withdrawRequestUuid,
					'player_id' => $request->player_id ?? null,
				]);
				return;
			}

			$player->notify(new WithdrawRequestedNotification($request));
			Log::info('Withdraw request email queued', [
				'uuid' => $event->withdrawRequestUuid,
				'player_id' => $request->player_id ?? null,
				'email' => $player->email ?? null,
			]);
		}
	}
