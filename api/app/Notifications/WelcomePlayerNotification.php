<?php

	namespace App\Notifications;

	use App\Support\MailSettings;
	use Illuminate\Bus\Queueable;
	use Illuminate\Notifications\Messages\MailMessage;
	use Illuminate\Notifications\Notification;
	use Illuminate\Support\Facades\Log;

	class WelcomePlayerNotification extends Notification
	{
		use Queueable;

		public function __construct(private ?string $mailCasinoKey = null)
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
				->subject('Welcome to ' . $mailConfig['casino_name'])
				->greeting('Hello' . ($username ? ' ' . $username : '') . '!')
				->line('Your account has been created successfully.')
				->line('Get ready to play and good luck!')
				->action('Log in to your account', $mailConfig['login_url'])
				->line('The ' . $mailConfig['casino_name'] . ' team wishes you big wins!')
				->markdown('notifications::email', ['mailConfig' => $mailConfig]);
		}
	}
