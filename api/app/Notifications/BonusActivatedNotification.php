<?php

namespace App\Notifications;

use App\Repositories\Crypto\Support\CurrencyDecimals;
use App\Repositories\Crypto\Support\Money;
use App\Models\BonusGrant;
use App\Support\MailSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BonusActivatedNotification extends Notification
{
	use Queueable;

	public function __construct(
		private BonusGrant $grant,
		private int $uiDecimals
	)
	{
	}

	public function via($notifiable): array
	{
		return ['database', 'mail'];
	}

	public function toMail($notifiable): MailMessage
	{
		$internalDecimals = CurrencyDecimals::internalForCurrency((string)$this->grant->currency_id);
		$amountUi = Money::baseToUi((string)$this->grant->amount_granted_base, $internalDecimals, $this->uiDecimals);
		$currency = (string)($this->grant->currency_code ?: $this->grant->currency_id);
		$mailConfig = MailSettings::resolve((string)($notifiable->int_casino_id ?? $this->grant->int_casino_id));
		app()->instance('mailsettings.current', $mailConfig);

		$ruleName = (string)($this->grant->meta['rule_name'] ?? 'Bonus');
		$username = isset($notifiable->username) ? (string)$notifiable->username : null;

		return (new MailMessage)
			->from(config('casino.email'), $mailConfig['casino_name'])
			->subject('Bonus activated - ' . $mailConfig['casino_name'])
			->greeting('Hello' . ($username ? ' ' . $username : '') . '!')
			->line('A new bonus has been activated on your account.')
			->line('Bonus: ' . $ruleName)
			->line('Amount: ' . $amountUi . ' ' . $currency)
			->line('Status: ' . (string)$this->grant->status)
			->line('Enjoy your game!')
			->markdown('notifications::email', ['mailConfig' => $mailConfig]);
	}

	public function toArray($notifiable): array
	{
		$internalDecimals = CurrencyDecimals::internalForCurrency((string)$this->grant->currency_id);
		return [
			'type' => 'bonus_activated',
			'bonus_grant_id' => (int)$this->grant->id,
			'bonus_rule_id' => $this->grant->bonus_rule_id ? (int)$this->grant->bonus_rule_id : null,
			'status' => (string)$this->grant->status,
			'currency' => (string)($this->grant->currency_code ?: $this->grant->currency_id),
			'amount' => Money::baseToUi((string)$this->grant->amount_granted_base, $internalDecimals, $this->uiDecimals),
			'rule_name' => (string)($this->grant->meta['rule_name'] ?? 'Bonus'),
		];
	}
}
