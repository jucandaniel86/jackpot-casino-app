<?php

	namespace App\Jobs;

	use App\Services\TournamentScoringService;
	use Illuminate\Bus\Queueable;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Foundation\Bus\Dispatchable;
	use Illuminate\Queue\SerializesModels;

	class ProcessTournamentWinJob implements ShouldQueue
	{
		use Queueable, SerializesModels, Dispatchable;

		public function __construct(public int $betId)
		{
		}

		public function handle(): void
		{
			app(TournamentScoringService::class)->processBetWin($this->betId);
		}
	}
