<?php

namespace App\Listeners;

use App\Repositories\Bonus\Services\BonusGrantService;
use App\Repositories\Crypto\Support\Money;
use App\Events\DepositDetected;
use App\Models\Player;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class GrantFirstDepositBonus
{
	public function __construct(
		private BonusGrantService $bonusGrantService
	)
	{
	}

	public function handle(DepositDetected $event): void
	{
		$wallet = $event->wallet;
		if (!$wallet || (int)$wallet->holder_id <= 0) {
			return;
		}

		if ((string)$wallet->holder_type !== Player::class) {
			return;
		}

		$player = Player::query()->find((int)$wallet->holder_id);
		if (!$player) {
			return;
		}

		$depositCount = Transaction::query()
			->from('transaction as t')
			->join('wallets as w', 'w.id', '=', 't.wallet_id')
			->where('w.holder_type', (string)$wallet->holder_type)
			->where('w.holder_id', (int)$wallet->holder_id)
			->where('t.type', 'deposit')
			->where('t.status', 'confirmed')
			->count();

		try {
			$depositAmountUi = Money::baseToUi((string)$event->amountBase, (int)$event->decimals, 8);
			$sourceRef = (string)$event->txid;
			$meta = [
				'deposit_txid' => (string)$event->txid,
				'deposit_wallet_id' => $wallet->id,
				'deposit_amount_base' => (string)$event->amountBase,
				'deposit_decimals' => (int)$event->decimals,
			];

			$grantedFirstDeposit = 0;
			if ($depositCount === 1) {
				$grantedFirstDeposit = $this->bonusGrantService->grantFirstDepositBonuses(
					player: $player,
					depositAmountUi: $depositAmountUi,
					sourceRef: $sourceRef,
					extraMeta: $meta
				);
			}

			$grantedDeposit = $this->bonusGrantService->grantDepositBonuses(
				player: $player,
				depositAmountUi: $depositAmountUi,
				sourceRef: $sourceRef,
				extraMeta: $meta
			);

			$granted = $grantedFirstDeposit + $grantedDeposit;

			Log::info('bonus.first_deposit.processed', [
				'player_id' => $player->id,
				'wallet_id' => $wallet->id,
				'deposit_txid' => $event->txid,
				'deposit_count' => $depositCount,
				'granted_first_deposit' => $grantedFirstDeposit,
				'granted_deposit' => $grantedDeposit,
				'granted' => $granted,
			]);
		} catch (\Throwable $e) {
			Log::error('bonus.first_deposit.failed', [
				'player_id' => $player->id,
				'wallet_id' => $wallet->id,
				'deposit_txid' => $event->txid,
				'error' => $e->getMessage(),
			]);
		}
	}
}
