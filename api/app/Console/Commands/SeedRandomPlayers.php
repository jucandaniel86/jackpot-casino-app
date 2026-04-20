<?php

	namespace App\Console\Commands;

	use Illuminate\Console\Command;
	use Illuminate\Support\Facades\Hash;
	use Illuminate\Support\Str;
	use App\Models\Player;

	class SeedRandomPlayers extends Command
	{
		protected $signature = 'players:seed-random {count=10} {--prefix=demo}';
		protected $description = 'Create random Player accounts (triggers observers / wallet creation)';

		public function handle(): int
		{
			$count = (int)$this->argument('count');
			$prefix = (string)$this->option('prefix');

			$created = 0;

			for ($i = 0; $i < $count; $i++) {
				$email = $prefix . '_' . Str::lower(Str::random(8)) . '@example.com';

				// adaptează câmpurile la schema ta reală
				$player = Player::create([
					"username" => $prefix . '_' . Str::random(6),
					"password" => Hash::make('Password123!'),
					"email" => $email,
					"active" => 0,
					"casino_id" => Str::uuid(),
					"wallet_id" => '',
					"fixed_id" => (string)random_int(100000, 999999)
				]);

				$created++;
				$this->line("Created player id={$player->id} email={$email}");
			}

			$this->info("Done. Created {$created} players.");
			return 0;
		}
	}