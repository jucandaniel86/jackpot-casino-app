<?php

namespace App\Enums;

enum RewardType: string
{
    case DAILY_REDEEM = 'daily_redeem';
    case EMAIL_SUBSCRIPTION_REWARD = 'email_subscription_reward';
    case REGISTER = 'register';
    case REGISTRATION_REWARD = 'registration_reward';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::DAILY_REDEEM => 'Daily Redeem',
            self::EMAIL_SUBSCRIPTION_REWARD => 'Email Subscription Reward',
            self::REGISTER => 'Register',
            self::REGISTRATION_REWARD => 'Registration Reward',
        };
    }

    public static function options(): array
    {
        return array_map(fn (self $type) => [
            'title' => $type->label(),
            'value' => $type->value,
        ], self::cases());
    }
}
