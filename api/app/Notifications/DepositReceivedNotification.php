<?php

	namespace App\Notifications;

	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Repositories\Crypto\Support\Money;
	use App\Models\Wallet;
	use App\Support\MailSettings;
	use Illuminate\Bus\Queueable;
	use Illuminate\Notifications\Messages\MailMessage;
	use Illuminate\Notifications\Notification;
	use Illuminate\Support\Facades\Log;

	class DepositReceivedNotification extends Notification
	{
		use Queueable;

		public function __construct(
			private Wallet  $wallet,
			private string  $amountBase,
			private int     $decimals,
			private string  $txid,
			private ?string $mailCasinoKey = null
		)
		{
		}

		public function via($notifiable): array
		{
			return ['database', 'mail'];
		}

		public function toMail($notifiable): MailMessage
		{
			$uiDecimals = CurrencyDecimals::uiForWallet($this->wallet);
			$amountUi = Money::baseToUi($this->amountBase, $this->decimals, $uiDecimals);
			$currency = $this->wallet->currency_code ?? $this->wallet->currency;
			$intCasinoId = $this->mailCasinoKey
				?? ($this->wallet->holder?->int_casino_id ?? ($notifiable->int_casino_id ?? null));
			$mailConfig = MailSettings::resolve($intCasinoId);
			app()->instance('mailsettings.current', $mailConfig);

			Log::info('mailConfig ' . json_encode($mailConfig));

			$username = isset($notifiable->username) ? $notifiable->username : null;
			return (new MailMessage)
				->from(config('casino.email'), $mailConfig['casino_name'])
				->subject('Deposit confirmed - ' . $mailConfig['casino_name'])
				->greeting('Hello' . ($username ? ' ' . $username : '') . '!')
				->line('A new deposit to your account was made.')
				->line('Amount: ' . $amountUi . ' ' . $currency)
				->line('TXID: ' . $this->txid)
				->line('Thank you for playing at ' . $mailConfig['casino_name'] . '!')
				->markdown('notifications::email', ['mailConfig' => $mailConfig]);
		}

		public function toArray($notifiable): array
		{
			$uiDecimals = CurrencyDecimals::uiForWallet($this->wallet);
			return [
				'type' => 'deposit',
				'currency' => $this->wallet->currency,
				'amount' => Money::baseToUi($this->amountBase, $this->decimals, $uiDecimals),
				'txid' => $this->txid,
			];
		}
	}
