<?php

	namespace App\Listeners;

	use App\Events\BetWinCreated;
	use App\Services\TournamentScoringService;

	class ProcessTournamentWinPoints
	{
		public function __construct(private TournamentScoringService $tournamentScoringService)
		{
		}

		public function handle(BetWinCreated $event): void
		{
			$this->tournamentScoringService->processBetWin((int)$event->bet->id);
		}
	}
