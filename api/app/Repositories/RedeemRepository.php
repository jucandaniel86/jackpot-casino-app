<?php

namespace App\Repositories;

use App\Interfaces\RedeemInterface;
use App\Models\Player;
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

    private const EMAIL_REWARD_TYPE = 'email_redeem';
    private const REWARD_AMOUNT = '1000';
    private const REWARD_CURRENCY = 'JKP';

    public function __construct(private readonly WalletLedgerService $ledger)
    {
    }

    public function claimEmailReward(string $email, ?string $casinoId = null): array
    {
        $email = strtolower(trim($email));

        $casinoId = $casinoId !== null ? trim($casinoId) : null;

        return DB::transaction(function () use ($email, $casinoId) {
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
                ->where('reward_type', self::EMAIL_REWARD_TYPE)
                ->first();

            if ($existingClaim) {
                return [
                    'success' => false,
                    'status' => 'already_redeemed',
                    'message' => 'This reward has already been redeemed.',
                ];
            }

            $wallet = $this->resolveRewardWallet($player);
            if (!$wallet) {
                return [
                    'success' => false,
                    'status' => 'missing_wallet',
                    'message' => 'Reward wallet is not available for this account.',
                ];
            }

            $decimals = CurrencyDecimals::internalForWallet($wallet);
            $amountBase = Money::uiToBase(self::REWARD_AMOUNT, $decimals);

            $claim = RewardClaim::query()->create([
                'player_id' => $player->id,
                'wallet_id' => $wallet->id,
                'int_casino_id' => $player->int_casino_id,
                'email' => $email,
                'reward_type' => self::EMAIL_REWARD_TYPE,
                'amount_base' => $amountBase,
                'amount' => self::REWARD_AMOUNT,
                'decimals' => $decimals,
                'currency_id' => (string)($wallet->currency_id ?: self::REWARD_CURRENCY),
                'currency_code' => (string)($wallet->currency_code ?: self::REWARD_CURRENCY),
                'status' => 'pending',
                'meta' => [
                    'source' => 'redeem_page',
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
                    'reward_type' => self::EMAIL_REWARD_TYPE,
                    'amount' => self::REWARD_AMOUNT,
                    'currency' => self::REWARD_CURRENCY,
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
                'message' => 'You received 1000 JKP.',
                'claim' => [
                    'id' => $claim->id,
                    'amount' => self::REWARD_AMOUNT,
                    'currency' => self::REWARD_CURRENCY,
                ],
            ];
        });
    }

    private function resolveRewardWallet(Player $player): ?Wallet
    {
        $wallet = Wallet::query()
            ->where('holder_type', $player->getMorphClass())
            ->where('holder_id', $player->id)
            ->where(function ($query) {
                $query->where('currency_code', self::REWARD_CURRENCY)
                    ->orWhere('currency_id', self::REWARD_CURRENCY)
                    ->orWhere('currency', self::REWARD_CURRENCY);
            })
            ->whereHas('type', function ($query) {
                $query->where('purpose', 'real');
            })
            ->first();

        if ($wallet) {
            $this->ledger->ensureBalanceRow($wallet);
            return $wallet;
        }

        $walletType = WalletType::query()
            ->where('active', 1)
            ->where('purpose', 'real')
            ->where(function ($query) {
                $query->where('currency_code', self::REWARD_CURRENCY)
                    ->orWhere('currency_id', self::REWARD_CURRENCY)
                    ->orWhere('code', self::REWARD_CURRENCY);
            })
            ->first();

        if (!$walletType) {
            return null;
        }

        /** @var Wallet $wallet */
        $wallet = Wallet::query()->create([
            'holder_type' => $player->getMorphClass(),
            'holder_id' => $player->id,
            'name' => ($player->fixed_id ?: 'player_' . $player->id) . '_' . self::REWARD_CURRENCY . '_real',
            'wallet_type_id' => $walletType->id,
            'uuid' => (string)Str::uuid(),
            'meta' => ['purpose' => 'real'],
            'balance' => 0,
            'currency' => $walletType->currency_id ?: self::REWARD_CURRENCY,
            'currency_id' => $walletType->currency_id ?: self::REWARD_CURRENCY,
            'currency_code' => $walletType->currency_code ?: self::REWARD_CURRENCY,
        ]);

        $this->ledger->ensureBalanceRow($wallet);

        return $wallet;
    }
}
