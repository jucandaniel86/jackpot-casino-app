<?php

	namespace App\Notifications;

	use App\Support\MailSettings;
	use Illuminate\Bus\Queueable;
	use Illuminate\Notifications\Messages\MailMessage;
	use Illuminate\Notifications\Notification;

	class PlayerResetPasswordNotification extends Notification
	{
		use Queueable;

		public function __construct(
			private string $resetUrl,
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
				->subject('Reset your password - ' . $mailConfig['casino_name'])
				->greeting('Hello' . ($username ? ' ' . $username : '') . '!')
				->line('We received a request to reset your password.')
				->action('Reset password', $this->resetUrl)
				->line('If you did not request a password reset, you can safely ignore this email.')
				->markdown('notifications::email', ['mailConfig' => $mailConfig]);
		}
	}
