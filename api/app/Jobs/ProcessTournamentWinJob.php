<?php

	namespace App\Jobs;

	use App\Enums\TransactionTypes;
	use App\Models\Bet;
	use App\Models\Tournament;
	use App\Models\TournamentScore;
	use Illuminate\Bus\Queueable;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Foundation\Bus\Dispatchable;
	use Illuminate\Queue\SerializesModels;
	use Illuminate\Support\Carbon;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Schema;

	class ProcessTournamentWinJob implements ShouldQueue
	{
		use Queueable, SerializesModels, Dispatchable;

		public function __construct(public int $betId)
		{
		}

		public function handle(): void
		{
			if (!Schema::hasTable('tournament_scores') || !Schema::hasTable('tournament_score_events')) {
				return;
			}

			$bet = Bet::query()->find($this->betId);
			if (!$bet) {
				return;
			}

			if ((string)$bet->transaction_type !== TransactionTypes::WIN->value) {
				return;
			}

			$whenPlaced = $bet->when_placed instanceof Carbon ? $bet->when_placed : Carbon::parse((string)$bet->when_placed);
			$gameId = (string)($bet->game_id ?? '');
			$userId = (int)$bet->user_id;

			if ($gameId === '' || $userId <= 0) {
				return;
			}

			$tournaments = Tournament::query()
				->where('status', 'active')
				->where('started_at', '<=', $whenPlaced)
				->where('ended_at', '>=', $whenPlaced)
				->whereExists(function ($q) use ($gameId) {
					$q->select(DB::raw(1))
						->from('tournament_games')
						->whereColumn('tournament_games.tournament_id', 'tournaments.id')
						->where('tournament_games.game_id', $gameId);
				})
				->get(['id']);

			if ($tournaments->isEmpty()) {
				return;
			}

			foreach ($tournaments as $tournament) {
				DB::transaction(function () use ($tournament, $bet, $userId, $whenPlaced) {
					$inserted = DB::table('tournament_score_events')->insertOrIgnore([
						'tournament_id' => $tournament->id,
						'bet_id' => (int)$bet->id,
						'bet_transaction_id' => $bet->transaction_id ?? null,
						'user_id' => $userId,
						'delta_points' => 1,
						'occurred_at' => $whenPlaced,
						'created_at' => now(),
						'updated_at' => now(),
					]);

					if ($inserted !== 1) {
						return;
					}

					$score = TournamentScore::query()
						->where('tournament_id', $tournament->id)
						->where('user_id', $userId)
						->lockForUpdate()
						->first();

					if (!$score) {
						TournamentScore::query()->create([
							'tournament_id' => $tournament->id,
							'user_id' => $userId,
							'points' => 1,
							'last_scored_at' => $whenPlaced,
						]);
						return;
					}

					$score->points = (int)$score->points + 1;
					$score->last_scored_at = $whenPlaced;
					$score->save();
				});
			}
		}
	}
