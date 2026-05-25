<?php

	namespace App\Observers;

	use App\Events\TournamentPointsAwarded;
	use App\Models\TournamentScoreEvent;

	class TournamentScoreEventObserver
	{
		public function created(TournamentScoreEvent $scoreEvent): void
		{
			TournamentPointsAwarded::dispatch($scoreEvent);
		}
	}
