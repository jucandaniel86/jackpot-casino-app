<?php

namespace App\Services;

use App\Enums\TransactionTypes;
use App\Models\Bet;
use App\Models\Tournament;
use App\Models\TournamentScore;
use App\Models\TournamentScoreEvent;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TournamentScoringService
{
	public function processBetWin(int $betId): void
	{
		if (!Schema::hasTable('tournament_scores') || !Schema::hasTable('tournament_score_events')) {
			return;
		}

		$bet = Bet::query()->find($betId);
		if (!$bet) {
			return;
		}

		if ((string)$bet->transaction_type !== TransactionTypes::WIN->value) {
			return;
		}

		// Some providers emit win transactions with zero payout on round close.
		// Tournament points should be awarded only for actual positive wins.
		if (bccomp((string)($bet->payout ?? '0'), '0', 8) !== 1) {
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
			->whereExists(function ($query) use ($gameId) {
				$query->select(DB::raw(1))
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
				if (!$this->createScoreEvent($tournament, $bet, $userId, $whenPlaced)) {
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

	private function createScoreEvent(Tournament $tournament, Bet $bet, int $userId, Carbon $whenPlaced): bool
	{
		try {
			TournamentScoreEvent::query()->create([
				'tournament_id' => $tournament->id,
				'bet_id' => (int)$bet->id,
				'bet_transaction_id' => $bet->transaction_id ?? null,
				'user_id' => $userId,
				'delta_points' => 1,
				'occurred_at' => $whenPlaced,
			]);
		} catch (QueryException $exception) {
			if ((string)$exception->getCode() === '23000') {
				return false;
			}

			throw $exception;
		}

		return true;
	}
}
