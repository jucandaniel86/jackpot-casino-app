<?php

	namespace App\Observers;

	use App\Enums\TransactionTypes;
	use App\Jobs\ProcessTournamentWinJob;
	use App\Models\Bet;

	class BetObserver
	{
		public function created(Bet $bet): void
		{
			if ((string)$bet->transaction_type !== TransactionTypes::WIN->value) {
				return;
			}

			ProcessTournamentWinJob::dispatch((int)$bet->id);
		}
	}

