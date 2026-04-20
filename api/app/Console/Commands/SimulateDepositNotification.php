<?php

	namespace App\Console\Commands;

	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Repositories\Crypto\Support\Money;
	use Illuminate\Console\Command;
	use App\Models\Player;
	use App\Models\Wallet;
	use App\Notifications\DepositReceivedNotification;
	use Illuminate\Support\Str;

	class SimulateDepositNotification extends Command
	{
		protected $signature = 'crypto:simulate-deposit 
                            {player_id? : Player ID (optional)}
                            {--amount= : Amount in UI (ex: 12.5)}
                            {--currency=PEP : Currency code}';

		protected $description = 'Simulate a deposit notification for frontend testing';

		public function handle(): int
		{
			$playerId = $this->argument('player_id');
			$currency = $this->option('currency');
			$amountUi = $this->option('amount') ?: (string)(rand(1, 500) / 10); // 0.1 – 50

			if ($playerId) {
				$players = Player::where('id', $playerId)->get();
			} else {
				$players = Player::inRandomOrder()->limit(3)->get();
			}

			if ($players->isEmpty()) {
				$this->error('No players found');
				return 1;
			}

			foreach ($players as $player) {
				$wallet = Wallet::where('holder_id', $player->id)
					->where('holder_type', Player::class)
					->where('currency', $currency)
					->first();

				if (!$wallet) {
					$this->warn("Player {$player->id} has no wallet {$currency}");
					continue;
				}

				$decimals = CurrencyDecimals::internalForWallet($wallet);
				$amountBase = Money::uiToBase($amountUi, $decimals);

				$txid = 'SIMULATED_' . Str::upper(Str::random(20));

				$player->notify(new DepositReceivedNotification(
					wallet: $wallet,
					amountBase: $amountBase,
					decimals: $decimals,
					txid: $txid
				));

				$this->info("Simulated deposit for player {$player->id}: {$amountUi} {$currency}");
			}

			return 0;
		}
	}
