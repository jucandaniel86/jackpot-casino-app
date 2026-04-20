<?php

namespace Tests\Feature;

use App\Repositories\Crypto\Contracts\TransactionWriterInterface;
use App\Repositories\Crypto\Services\GameWalletService;
use App\Repositories\Crypto\Services\WalletLedgerService;
use App\Models\Player;
use App\Models\Wallet;
use App\Models\WalletBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class GameWalletServiceTest extends TestCase
{
	use RefreshDatabase;

	private function makeService(): GameWalletService
	{
		$writer = new class implements TransactionWriterInterface {
			public function writeGameTransaction(
				Wallet $wallet,
				string $type,
				string $status,
				string $amountBase,
				int $decimals,
				array $meta = []
			): void {
			}

			public function writeDepositTransaction(
				Wallet $wallet,
				string $status,
				string $amountBase,
				int $decimals,
				string $txid,
				?string $fromAddress = null,
				array $meta = []
			): void {
			}

			public function writeWithdrawTransaction(
				Wallet $wallet,
				string $status,
				string $amountBase,
				int $decimals,
				string $txid,
				string $toAddress,
				array $meta = []
			): void {
			}
		};

		return new GameWalletService(new WalletLedgerService(), $writer);
	}

	private function seedWalletWithBalance(string $availableBase, int $decimals): Wallet
	{
		$player = Player::create([
			'username' => 'player_' . Str::random(8),
			'email' => Str::random(8) . '@example.test',
			'password' => bcrypt('secret'),
			'active' => 1,
		]);

		$walletId = DB::table('wallets')->insertGetId([
			'holder_type' => $player->getMorphClass(),
			'holder_id' => $player->id,
			'name' => 'test_wallet_' . $player->id,
			'wallet_type_id' => 1,
			'uuid' => (string)Str::uuid(),
			'meta' => json_encode(['decimals' => $decimals]),
			'balance' => 0,
			'currency' => 'TEST',
			'currency_id' => 'TEST',
			'currency_code' => 'TEST',
			'network' => 'TESTNET',
		]);

		WalletBalance::create([
			'wallet_id' => $walletId,
			'currency' => 'TEST',
			'available_base' => $availableBase,
			'reserved_base' => '0',
		]);

		return Wallet::query()->findOrFail($walletId);
	}

	public function test_place_bet_decreases_balance_and_fails_when_insufficient_funds(): void
	{
		$wallet = $this->seedWalletWithBalance('1000', 2);
		$svc = $this->makeService();

		$ctx = [
			'provider' => 'gameforge',
			'provider_tx_id' => 'bet-1',
			'round_id' => 'round-1',
		];

		$svc->placeBet($wallet, '250', 2, $ctx);

		$available = WalletBalance::query()
			->where('wallet_id', $wallet->id)
			->value('available_base');
		$this->assertSame('750', (string)$available);

		try {
			$ctx['provider_tx_id'] = 'bet-2';
			$svc->placeBet($wallet, '5000', 2, $ctx);
			$this->fail('Expected insufficient funds exception.');
		} catch (\RuntimeException $e) {
			$this->assertSame('INSUFFICIENT_FUNDS', $e->getMessage());
		}

		$availableAfter = WalletBalance::query()
			->where('wallet_id', $wallet->id)
			->value('available_base');
		$this->assertSame('750', (string)$availableAfter);
	}

	public function test_apply_win_increases_balance(): void
	{
		$wallet = $this->seedWalletWithBalance('500', 2);
		$svc = $this->makeService();

		$ctx = [
			'provider' => 'gameforge',
			'provider_tx_id' => 'win-1',
			'round_id' => 'round-1',
		];

		$svc->applyWin($wallet, '200', 2, $ctx);

		$available = WalletBalance::query()
			->where('wallet_id', $wallet->id)
			->value('available_base');
		$this->assertSame('700', (string)$available);
	}
}
