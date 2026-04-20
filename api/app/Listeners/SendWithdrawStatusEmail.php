<?php

	namespace App\Listeners;

	use App\Events\WithdrawApproved;
	use App\Events\WithdrawCompleted;
	use App\Events\WithdrawRejected;
	use App\Models\WithdrawRequest;
	use App\Notifications\WithdrawStatusNotification;
	use Illuminate\Support\Facades\Log;

	class SendWithdrawStatusEmail
	{
		public function handle(object $event): void
		{
			$status = match (true) {
				$event instanceof WithdrawApproved => 'approved',
				$event instanceof WithdrawRejected => 'rejected',
				$event instanceof WithdrawCompleted => 'completed',
				default => null,
			};

			if (!$status || empty($event->withdrawRequestUuid)) {
				Log::warning('Withdraw status email skipped: missing status/uuid', [
					'event' => $event::class,
					'status' => $status,
					'uuid' => $event->withdrawRequestUuid ?? null,
				]);
				return;
			}

			$request = WithdrawRequest::query()
				->where('uuid', $event->withdrawRequestUuid)
				->first();

			if (!$request) {
				Log::warning('Withdraw status email skipped: request not found', [
					'uuid' => $event->withdrawRequestUuid,
				]);
				return;
			}

			$player = $request->player;
			if (!$player || !method_exists($player, 'notify')) {
				Log::warning('Withdraw status email skipped: player missing or not notifiable', [
					'uuid' => $event->withdrawRequestUuid,
					'player_id' => $request->player_id ?? null,
				]);
				return;
			}

			$player->notify(new WithdrawStatusNotification($request, $status));
			Log::info('Withdraw status email queued', [
				'uuid' => $event->withdrawRequestUuid,
				'status' => $status,
				'player_id' => $request->player_id ?? null,
				'email' => $player->email ?? null,
			]);
		}
	}
