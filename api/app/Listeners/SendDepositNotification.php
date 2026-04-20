<?php

	namespace App\Listeners;

	use App\Events\DepositDetected;
	use App\Notifications\DepositReceivedNotification;
	use App\Support\MailSettings;
	use Illuminate\Support\Facades\Notification;
	class SendDepositNotification
	{
	public function handle(DepositDetected $event): void
	{
		$holderType = $event->wallet->holder_type ?? null;
		$holderId = $event->wallet->holder_id ?? null;
		if (!$holderType || !$holderId || !class_exists($holderType)) {
			return;
		}

		$player = (new $holderType())->newQuery()->find($holderId);
		if (!$player || !method_exists($player, 'notify')) {
			return;
		}

		$player->notify(new DepositReceivedNotification(
			$event->wallet,
			$event->amountBase,
				$event->decimals,
				$event->txid
			));

		$intCasinoId = $player->int_casino_id ?? ($event->wallet->holder?->int_casino_id ?? null);
		$mailConfig = MailSettings::resolve($intCasinoId);
		$adminEmail = $mailConfig['admin_email'] ?? null;
		if ($adminEmail) {
			Notification::route('mail', $adminEmail)
				->notify(new DepositReceivedNotification(
					$event->wallet,
					$event->amountBase,
					$event->decimals,
					$event->txid,
					$intCasinoId ? (string)$intCasinoId : null
				));
		}
		}
	}
