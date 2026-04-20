<?php

namespace App\Notifications;

use App\Support\MailSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlayerLoginVerificationCodeNotification extends Notification
{
	use Queueable;

	public function __construct(
		private string $code,
		private ?string $mailCasinoKey = null
	)
	{
	}

	public function via($notifiable): array
	{
		return ['mail'];
	}

	public function toMail($notifiable): MailMessage
	{
		$intCasinoId = $this->mailCasinoKey ?? ($notifiable->int_casino_id ?? null);
		$mailConfig = MailSettings::resolve($intCasinoId);
		app()->instance('mailsettings.current', $mailConfig);
		$username = isset($notifiable->username) ? $notifiable->username : null;

		return (new MailMessage)
			->from(config('casino.email'), $mailConfig['casino_name'])
			->subject('Your login verification code - ' . $mailConfig['casino_name'])
			->greeting('Hello' . ($username ? ' ' . $username : '') . '!')
			->line('Use the code below to finish logging in:')
			->line('Verification code: ' . $this->code)
			->line('This code expires in 30 minutes.')
			->line('If this was not you, please reset your password immediately.')
			->markdown('notifications::email', ['mailConfig' => $mailConfig]);
	}
}
