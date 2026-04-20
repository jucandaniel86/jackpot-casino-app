<?php

	namespace App\Notifications;

	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Repositories\Crypto\Support\Money;
	use App\Models\WithdrawRequest;
	use App\Support\MailSettings;
	use Illuminate\Bus\Queueable;
	use Illuminate\Notifications\Messages\MailMessage;
	use Illuminate\Notifications\Notification;

	class WithdrawStatusNotification extends Notification
	{
		use Queueable;

		public function __construct(
			private WithdrawRequest $request,
			private string          $status,
			private ?string         $mailCasinoKey = null
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
			$subject = match ($this->status) {
				'approved' => 'Withdraw approved - ' . $mailConfig['casino_name'],
				'rejected' => 'Withdraw rejected - ' . $mailConfig['casino_name'],
				'completed' => 'Withdraw finalized - ' . $mailConfig['casino_name'],
				default => 'Update withdraw - ' . $mailConfig['casino_name'],
			};

			$username = isset($notifiable->username) ? $notifiable->username : null;
			$message = (new MailMessage)
				->from(config('casino.email'), $mailConfig['casino_name'])
				->subject($subject)
				->greeting('Hello' . ($username ? ' ' . $username : '') . '!')
				->line('Your withdrawal request has been updated.')
				->line('Status: ' . strtoupper($this->status))
				->line('Amount: ' . $amountUi . ' ' . $currency);

			if ($this->status === 'rejected' && $this->request->reject_reason) {
				$message->line('Reason: ' . $this->request->reject_reason);
			}

			if ($this->status === 'completed' && $this->request->txid) {
				$message->line('TXID: ' . $this->request->txid);
			}

			return $message
				->line('Thank you for playing at ' . $mailConfig['casino_name'] . '!')
				->markdown('notifications::email', ['mailConfig' => $mailConfig]);
		}
	}
