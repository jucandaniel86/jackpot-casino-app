<?php

	namespace App\Notifications;

	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Repositories\Crypto\Support\Money;
	use App\Models\WithdrawRequest;
	use App\Support\MailSettings;
	use Illuminate\Bus\Queueable;
	use Illuminate\Notifications\Messages\MailMessage;
	use Illuminate\Notifications\Notification;

	class WithdrawRequestedNotification extends Notification
	{
		use Queueable;

		public function __construct(
			private WithdrawRequest $request,
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
			$currency = $this->request->currency;
			$internalDecimals = CurrencyDecimals::internalForCurrency((string)$currency);
			$uiDecimals = CurrencyDecimals::uiForCurrency((string)$currency);
			$amountUi = $this->request->amount_ui
				?? Money::baseToUi((string)$this->request->amount_base, $internalDecimals, $uiDecimals);
			$intCasinoId = $this->mailCasinoKey
				?? ($this->request->player?->int_casino_id ?? ($notifiable->int_casino_id ?? null));
			$mailConfig = MailSettings::resolve($intCasinoId);
			app()->instance('mailsettings.current', $mailConfig);

			$username = isset($notifiable->username) ? $notifiable->username : null;
			return (new MailMessage)
				->from(config('casino.email'), $mailConfig['casino_name'])
				->subject('Withdraw request received - ' . $mailConfig['casino_name'])
				->greeting('Hello' . ($username ? ' ' . $username : '') . '!')
				->line('We received your withdrawal request.')
				->line('Amount: ' . $amountUi . ' ' . $currency)
				->line('To address: ' . $this->request->to_address)
				->line('Status: PENDING')
				->line('Thank you for playing at ' . $mailConfig['casino_name'] . '!')
				->markdown('notifications::email', ['mailConfig' => $mailConfig]);
		}
	}
