<?php

	namespace App\Observers;

	use App\Enums\TransactionTypes;
	use App\Models\Bet;
	use App\Services\TournamentScoringService;

	class BetObserver
	{
		public function __construct(private TournamentScoringService $tournamentScoringService)
		{
		}

		public function created(Bet $bet): void
		{
			if ((string)$bet->transaction_type !== TransactionTypes::WIN->value) {
				return;
			}

			$this->tournamentScoringService->processBetWin((int)$bet->id);
		}
	}
