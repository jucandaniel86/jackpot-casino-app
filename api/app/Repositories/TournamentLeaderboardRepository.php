<?php

	namespace App\Repositories;

	use App\Models\Tournament;
	use App\Models\TournamentPrize;
	use Illuminate\Database\Eloquent\ModelNotFoundException;
	use Illuminate\Support\Collection;
	use Illuminate\Support\Facades\DB;

	class TournamentLeaderboardRepository
	{
		public function getTournament(string $id): Tournament
		{
			$tournament = Tournament::query()
				->with(['prizes'])
				->find($id);

			if (!$tournament) {
				throw (new ModelNotFoundException())->setModel(Tournament::class, [$id]);
			}

			return $tournament;
		}

		/**
		 * @return array{
		 *   players_count: int,
		 *   leaderboard: array<int, array{position:int, player:string, score:int, prize_label:string}>
		 * }
		 */
		public function leaderboard(string $tournamentId, int $limit = 20): array
		{
			$limit = max(1, min(200, $limit));
			$tournament = $this->getTournament($tournamentId);

			$rows = DB::table('tournament_scores as ts')
				->join('players as p', 'p.id', '=', 'ts.user_id')
				->where('ts.tournament_id', $tournament->id)
				->orderByDesc('ts.points')
				->orderBy('ts.updated_at')
				->orderBy('ts.user_id')
				->limit($limit)
				->get([
					'ts.user_id as user_id',
					'ts.points as points',
					'p.username as username',
				]);

			$playersCount = (int)DB::table('tournament_scores')
				->where('tournament_id', $tournament->id)
				->count();

			$leaderboard = [];
			$position = 1;
			foreach ($rows as $row) {
				$score = (int)$row->points;
				$leaderboard[] = [
					'position' => $position,
					'player' => (string)$row->username,
					'score' => $score,
					'prize_label' => $this->prizeLabelFor($tournament->prizes, $position, $score),
				];
				$position++;
			}

			return [
				'players_count' => $playersCount,
				'leaderboard' => $leaderboard,
			];
		}

		/**
		 * @return array{position:int, username:string, score:int, badge_text:?string, badge_variant:?string, est_prize_label:?string}|null
		 */
		public function userStanding(string $tournamentId, int $userId): ?array
		{
			$tournament = $this->getTournament($tournamentId);

			$row = DB::table('tournament_scores as ts')
				->join('players as p', 'p.id', '=', 'ts.user_id')
				->where('ts.tournament_id', $tournament->id)
				->where('ts.user_id', $userId)
				->first([
					'ts.user_id as user_id',
					'ts.points as points',
					'ts.updated_at as updated_at',
					'p.username as username',
				]);

			if (!$row) {
				return null;
			}

			$score = (int)$row->points;
			$updatedAt = (string)$row->updated_at;

			$aheadCount = (int)DB::table('tournament_scores as ts')
				->where('ts.tournament_id', $tournament->id)
				->where(function ($q) use ($score, $updatedAt, $userId) {
					$q->where('ts.points', '>', $score)
						->orWhere(function ($q) use ($score, $updatedAt, $userId) {
							$q->where('ts.points', '=', $score)
								->where(function ($q) use ($updatedAt, $userId) {
									$q->where('ts.updated_at', '<', $updatedAt)
										->orWhere(function ($q) use ($updatedAt, $userId) {
											$q->where('ts.updated_at', '=', $updatedAt)
												->where('ts.user_id', '<', $userId);
										});
								});
						});
				})
				->count();

			$position = $aheadCount + 1;
			$estPrizeLabel = $this->prizeLabelFor($tournament->prizes, $position, $score);

			$badgeText = null;
			$badgeVariant = null;
			if ($position === 1) {
				$badgeText = 'Leader';
				$badgeVariant = 'success';
			} elseif ($estPrizeLabel !== '-') {
				$badgeText = 'In prize';
				$badgeVariant = 'info';
			}

			return [
				'position' => $position,
				'username' => (string)$row->username,
				'score' => $score,
				'badge_text' => $badgeText,
				'badge_variant' => $badgeVariant,
				'est_prize_label' => $estPrizeLabel !== '-' ? $estPrizeLabel : null,
			];
		}

		private function prizeLabelFor(Collection $prizes, int $position, int $score): string
		{
			$rankPrize = $prizes
				->where('prize_type', 'rank')
				->first(function (TournamentPrize $prize) use ($position) {
					$from = $prize->rank_from !== null ? (int)$prize->rank_from : null;
					$to = $prize->rank_to !== null ? (int)$prize->rank_to : null;

					if ($from === null || $to === null) {
						return false;
					}
					return $position >= $from && $position <= $to;
				});

			if ($rankPrize) {
				return (string)$rankPrize->prize_name;
			}

			$thresholdPrize = $prizes
				->where('prize_type', 'threshold')
				->filter(fn (TournamentPrize $prize) => $prize->min_points !== null && $score >= (int)$prize->min_points)
				->sortByDesc(fn (TournamentPrize $prize) => (int)$prize->min_points)
				->first();

			if ($thresholdPrize) {
				return (string)$thresholdPrize->prize_name;
			}

			return '-';
		}
	}

