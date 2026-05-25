<?php

	namespace App\Events;

	use App\Models\TournamentScoreEvent;
	use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
	use Illuminate\Foundation\Events\Dispatchable;
	use Illuminate\Queue\SerializesModels;

	class TournamentPointsAwarded implements ShouldDispatchAfterCommit
	{
		use Dispatchable, SerializesModels;

		public function __construct(public TournamentScoreEvent $scoreEvent)
		{
		}
	}
