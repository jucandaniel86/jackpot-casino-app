<?php

namespace App\Repositories;

use App\Enums\RewardType;
use App\Interfaces\RedeemInterface;
use App\Models\Player;
use App\Models\Reward;
use App\Models\RewardClaim;
use App\Models\Wallet;
use App\Models\WalletType;
use App\Repositories\Crypto\Services\WalletLedgerService;
use App\Repositories\Crypto\Support\CurrencyDecimals;
use App\Repositories\Crypto\Support\Money;
use App\Traits\QueryTrait;
use App\Traits\UploadFilesTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RedeemRepository implements RedeemInterface
{
    use QueryTrait, UploadFilesTrait;

    private const REWARD_AMOUNT = '1000';
    private const REWARD_CURRENCY = 'JKP';

    public function __construct(private readonly WalletLedgerService $ledger)
    {
    }

    public function claimEmailReward(string $email, ?string $casinoId = null, ?string $rewardUid = null): array
    {
        $email = strtolower(trim($email));

        $casinoId = $casinoId !== null ? trim($casinoId) : null;
        $rewardUid = $rewardUid !== null ? trim($rewardUid) : null;

        return DB::transaction(function () use ($email, $casinoId, $rewardUid) {
            $reward = $this->resolveReward(
                type: RewardType::EMAIL_SUBSCRIPTION_REWARD,
                casinoId: $casinoId,
                rewardUid: $rewardUid,
            );

            if (!$reward) {
                return [
                    'success' => false,
                    'status' => 'reward_not_found',
                    'message' => 'This reward is not available.',
                ];
            }

            $playerQuery = Player::query()->whereRaw('LOWER(email) = ?', [$email]);

            if ($casinoId !== null && $casinoId !== '') {
                $playerQuery->where('int_casino_id', $casinoId);
            }

            /** @var Player|null $player */
            $player = $playerQuery->lockForUpdate()->first();

            if (!$player) {
                return [
                    'success' => false,
                    'status' => 'not_found',
                    'message' => 'No account found for this email address.',
                ];
            }

            $existingClaim = RewardClaim::query()
                ->where('player_id', $player->id)
                ->where('reward_id', $reward->id)
                ->where('period_key', $this->periodKey($reward))
                ->first();

            if ($existingClaim) {
                return [
                    'success' => false,
                    'status' => 'already_redeemed',
                    'message' => 'This reward has already been redeemed.',
                ];
            }

            $currency = $this->rewardCurrency($reward);
            $amount = $this->rewardAmount($reward);
            $walletPurpose = $this->walletPurpose($reward);

            $wallet = $this->resolveRewardWallet($player, $currency, $walletPurpose);
            if (!$wallet) {
                return [
                    'success' => false,
                    'status' => 'missing_wallet',
                    'message' => 'Reward wallet is not available for this account.',
                ];
            }

            $decimals = CurrencyDecimals::internalForWallet($wallet);
            $amountBase = Money::uiToBase($amount, $decimals);
            $periodKey = $this->periodKey($reward);

            $claim = RewardClaim::query()->create([
                'player_id' => $player->id,
                'wallet_id' => $wallet->id,
                'int_casino_id' => $player->int_casino_id,
                'reward_id' => $reward->id,
                'period_key' => $periodKey,
                'email' => $email,
                'amount_base' => $amountBase,
                'amount' => $amount,
                'decimals' => $decimals,
                'currency_id' => (string)($wallet->currency_id ?: $currency),
                'currency_code' => (string)($wallet->currency_code ?: $currency),
                'status' => 'pending',
                'meta' => [
                    'source' => 'redeem_page',
                    'reward_uid' => $reward->uid,
                    'reward_type' => $reward->type,
                ],
            ]);

            $this->ledger->creditAvailable(
                wallet: $wallet,
                type: 'reward_claim',
                amountBase: $amountBase,
                decimals: $decimals,
                idempotencyKey: 'reward_claim:' . $claim->id,
                referenceType: 'reward_claim',
                referenceId: (string)$claim->id,
                meta: [
                    'reward_id' => $reward->id,
                    'reward_uid' => $reward->uid,
                    'reward_type' => $reward->type,
                    'period_key' => $periodKey,
                    'amount' => $amount,
                    'currency' => $currency,
                    'email' => $email,
                ],
            );

            $claim->update([
                'status' => 'claimed',
                'claimed_at' => now(),
            ]);

            return [
                'success' => true,
                'status' => 'claimed',
                'message' => 'You received ' . $amount . ' ' . $currency . '.',
                'claim' => [
                    'id' => $claim->id,
                    'amount' => $amount,
                    'currency' => $currency,
                ],
            ];
        });
    }

    public function claimPlayerReward(string $rewardUid, Player $player, ?string $casinoId = null): array
    {
        $rewardUid = trim($rewardUid);
        $casinoId = $casinoId !== null ? trim($casinoId) : $player->int_casino_id;

        return DB::transaction(function () use ($rewardUid, $player, $casinoId) {
            $reward = $this->resolveReward(
                type: RewardType::DAILY_REDEEM,
                casinoId: $casinoId,
                rewardUid: $rewardUid,
            );

            if (!$reward) {
                return [
                    'success' => false,
                    'status' => 'reward_not_found',
                    'message' => 'This reward is not available.',
                ];
            }

            $lockedPlayer = Player::query()
                ->where('id', $player->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedPlayer) {
                return [
                    'success' => false,
                    'status' => 'not_found',
                    'message' => 'Player account was not found.',
                ];
            }

            $periodKey = $this->periodKey($reward);

            $existingClaim = RewardClaim::query()
                ->where('player_id', $lockedPlayer->id)
                ->where('reward_id', $reward->id)
                ->where('period_key', $periodKey)
                ->first();

            if ($existingClaim) {
                return [
                    'success' => false,
                    'status' => 'already_redeemed',
                    'message' => $this->alreadyRedeemedMessage($reward),
                ];
            }

            $currency = $this->rewardCurrency($reward);
            $amount = $this->rewardAmount($reward);
            $walletPurpose = $this->walletPurpose($reward);

            $wallet = $this->resolveRewardWallet($lockedPlayer, $currency, $walletPurpose);
            if (!$wallet) {
                return [
                    'success' => false,
                    'status' => 'missing_wallet',
                    'message' => 'Reward wallet is not available for this account.',
                ];
            }

            $decimals = CurrencyDecimals::internalForWallet($wallet);
            $amountBase = Money::uiToBase($amount, $decimals);

            $claim = RewardClaim::query()->create([
                'player_id' => $lockedPlayer->id,
                'wallet_id' => $wallet->id,
                'int_casino_id' => $lockedPlayer->int_casino_id,
                'reward_id' => $reward->id,
                'period_key' => $periodKey,
                'email' => (string)$lockedPlayer->email,
                'amount_base' => $amountBase,
                'amount' => $amount,
                'decimals' => $decimals,
                'currency_id' => (string)($wallet->currency_id ?: $currency),
                'currency_code' => (string)($wallet->currency_code ?: $currency),
                'status' => 'pending',
                'meta' => [
                    'source' => 'redeem_page',
                    'reward_uid' => $reward->uid,
                    'reward_type' => $reward->type,
                ],
            ]);

            $this->ledger->creditAvailable(
                wallet: $wallet,
                type: 'reward_claim',
                amountBase: $amountBase,
                decimals: $decimals,
                idempotencyKey: 'reward_claim:' . $claim->id,
                referenceType: 'reward_claim',
                referenceId: (string)$claim->id,
                meta: [
                    'reward_id' => $reward->id,
                    'reward_uid' => $reward->uid,
                    'reward_type' => $reward->type,
                    'period_key' => $periodKey,
                    'amount' => $amount,
                    'currency' => $currency,
                    'email' => $lockedPlayer->email,
                ],
            );

            $claim->update([
                'status' => 'claimed',
                'claimed_at' => now(),
            ]);

            return [
                'success' => true,
                'status' => 'claimed',
                'message' => 'You received ' . $amount . ' ' . $currency . '.',
                'claim' => [
                    'id' => $claim->id,
                    'amount' => $amount,
                    'currency' => $currency,
                    'period_key' => $periodKey,
                ],
            ];
        });
    }

    private function resolveRewardWallet(Player $player, string $currency, string $purpose = 'real'): ?Wallet
    {
        $wallet = Wallet::query()
            ->where('holder_type', $player->getMorphClass())
            ->where('holder_id', $player->id)
            ->where(function ($query) use ($currency) {
                $query->where('currency_code', $currency)
                    ->orWhere('currency_id', $currency)
                    ->orWhere('currency', $currency);
            })
            ->whereHas('type', function ($query) use ($purpose) {
                $query->where('purpose', $purpose);
            })
            ->first();

        if ($wallet) {
            $this->ledger->ensureBalanceRow($wallet);
            return $wallet;
        }

        $walletType = WalletType::query()
            ->where('active', 1)
            ->where('purpose', $purpose)
            ->where(function ($query) use ($currency) {
                $query->where('currency_code', $currency)
                    ->orWhere('currency_id', $currency)
                    ->orWhere('code', $currency);
            })
            ->first();

        if (!$walletType) {
            return null;
        }

        /** @var Wallet $wallet */
        $wallet = Wallet::query()->create([
            'holder_type' => $player->getMorphClass(),
            'holder_id' => $player->id,
            'name' => ($player->fixed_id ?: 'player_' . $player->id) . '_' . $currency . '_' . $purpose,
            'wallet_type_id' => $walletType->id,
            'uuid' => (string)Str::uuid(),
            'meta' => ['purpose' => $purpose],
            'balance' => 0,
            'currency' => $walletType->currency_id ?: $currency,
            'currency_id' => $walletType->currency_id ?: $currency,
            'currency_code' => $walletType->currency_code ?: $currency,
        ]);

        $this->ledger->ensureBalanceRow($wallet);

        return $wallet;
    }

    private function resolveReward(RewardType $type, ?string $casinoId = null, ?string $rewardUid = null): ?Reward
    {
        return Reward::query()
            ->where('type', $type->value)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->when($rewardUid !== null && $rewardUid !== '', function ($query) use ($rewardUid) {
                $query->where('uid', $rewardUid);
            })
            ->when($casinoId !== null && $casinoId !== '', function ($query) use ($casinoId) {
                $query->where(function ($query) use ($casinoId) {
                    $query->where('int_casino_id', $casinoId)
                        ->orWhereNull('int_casino_id');
                });
            }, function ($query) {
                $query->whereNull('int_casino_id');
            })
            ->orderByRaw('CASE WHEN int_casino_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('page_order')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
    }

    private function rewardAmount(Reward $reward): string
    {
        $amount = $reward->rule['amount'] ?? self::REWARD_AMOUNT;

        return is_numeric($amount) ? (string)$amount : self::REWARD_AMOUNT;
    }

    private function rewardCurrency(Reward $reward): string
    {
        $currency = $reward->rule['currency'] ?? self::REWARD_CURRENCY;

        return is_string($currency) && trim($currency) !== '' ? trim($currency) : self::REWARD_CURRENCY;
    }

    private function walletPurpose(Reward $reward): string
    {
        $purpose = $reward->rule['wallet_purpose'] ?? 'real';

        return is_string($purpose) && trim($purpose) !== '' ? trim($purpose) : 'real';
    }

    private function rewardFrequency(Reward $reward): string
    {
        $frequency = $reward->rule['frequency'] ?? 'once';

        return is_string($frequency) && trim($frequency) !== '' ? trim($frequency) : 'once';
    }

    private function rewardTimezone(Reward $reward): string
    {
        $timezone = $reward->rule['timezone'] ?? config('app.timezone', 'UTC');

        return is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)
            ? $timezone
            : config('app.timezone', 'UTC');
    }

    private function periodKey(Reward $reward): string
    {
        $now = now($this->rewardTimezone($reward));

        return match ($this->rewardFrequency($reward)) {
            'daily' => $now->format('Y-m-d'),
            'weekly' => $now->format('o-\WW'),
            'monthly' => $now->format('Y-m'),
            default => 'lifetime',
        };
    }

    private function alreadyRedeemedMessage(Reward $reward): string
    {
        return match ($this->rewardFrequency($reward)) {
            'daily' => 'This reward has already been redeemed today.',
            'weekly' => 'This reward has already been redeemed this week.',
            'monthly' => 'This reward has already been redeemed this month.',
            default => 'This reward has already been redeemed.',
        };
    }
}
