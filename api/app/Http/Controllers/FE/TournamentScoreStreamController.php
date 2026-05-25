<?php

	namespace App\Http\Controllers\FE;

	use App\Http\Controllers\Controller;
	use App\Models\Game;
	use App\Models\Tournament;
	use App\Repositories\TournamentLeaderboardRepository;
	use Illuminate\Database\Eloquent\ModelNotFoundException;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;

	class TournamentScoreStreamController extends Controller
	{
		public function __invoke(string $slug, Request $request, TournamentLeaderboardRepository $leaderboardRepository)
		{
			$user = $request->user();
			$tournament = $this->activeTournamentForGame($slug);

			if (!$tournament) {
				return response()->json([
					'message' => 'No active tournament found for this game.',
				], 404);
			}

			$lastId = $request->header('Last-Event-ID');
			$initialCursor = $lastId ? (int)$lastId : 0;

			return response()->stream(function () use ($user, $tournament, $leaderboardRepository, $initialCursor) {
				@set_time_limit(0);
				@ignore_user_abort(false);
				@ini_set('zlib.output_compression', '0');
				@ini_set('output_buffering', 'off');

				$cursor = $initialCursor;
				$lastStandingSignature = null;
				$this->emitStreamPadding();
				$this->emitRetryDirective();
				$this->emitHeartbeat();

				$standingSnapshot = $this->standingSnapshot($tournament, (int)$user->id, $leaderboardRepository);
				$lastStandingSignature = $standingSnapshot['signature'];
				$this->emitStandingEvent($tournament, $cursor, $standingSnapshot);

				while (true) {
					if (connection_aborted()) {
						break;
					}

					$standingSnapshot = $this->standingSnapshot($tournament, (int)$user->id, $leaderboardRepository);

					if ($standingSnapshot['signature'] !== $lastStandingSignature) {
						$cursor++;
						$lastStandingSignature = $standingSnapshot['signature'];
						$this->emitStandingEvent($tournament, $cursor, $standingSnapshot);
					}

					$this->emitHeartbeat();

					if (connection_aborted()) {
						break;
					}

					sleep(1);
				}
			}, 200, [
				'Content-Type' => 'text/event-stream; charset=UTF-8',
				'Cache-Control' => 'no-cache, no-transform',
				'Connection' => 'keep-alive',
				'X-Accel-Buffering' => 'no',
			]);
		}

		private function activeTournamentForGame(string $slug): ?Tournament
		{
			$game = Game::query()
				->where('slug', $slug)
				->where('active_on_site', 1)
				->first(['id', 'game_id']);

			if (!$game) {
				return null;
			}

			return Tournament::query()
				->where('status', 'active')
				->where('started_at', '<=', now())
				->where('ended_at', '>=', now())
				->whereExists(function ($query) use ($game) {
					$query->select(DB::raw(1))
						->from('tournament_games')
						->whereColumn('tournament_games.tournament_id', 'tournaments.id')
						->where('tournament_games.game_id', $game->game_id);
				})
				->orderBy('ended_at')
				->first();
		}

		private function emitStandingEvent(
			Tournament $tournament,
			int $cursor,
			array $standing
		): void {
			echo "id: {$cursor}\n";
			echo "event: standing\n";
			echo "data: " . json_encode([
					'tournament_id' => (string)$tournament->id,
					'rank' => $standing['rank'] ?? null,
					'points' => $standing['points'] ?? 0,
					'updated_at' => $standing['updated_at'] ?? null,
				]) . "\n\n";
			echo ": " . str_repeat(' ', 2048) . "\n\n";

			$this->flushStream();
		}

		private function emitStreamPadding(): void
		{
			echo ": " . str_repeat(' ', 4096) . "\n\n";
			$this->flushStream();
		}

		private function standingSnapshot(
			Tournament $tournament,
			int $userId,
			TournamentLeaderboardRepository $leaderboardRepository
		): array {
			$row = DB::table('tournament_scores')
				->where('tournament_id', $tournament->id)
				->where('user_id', $userId)
				->first(['points', 'updated_at']);

			try {
				$standing = $leaderboardRepository->userStanding((string)$tournament->id, $userId);
			} catch (ModelNotFoundException) {
				$standing = null;
			}

			$points = (int)($standing['score'] ?? $row?->points ?? 0);
			$rank = $standing['position'] ?? null;
			$updatedAt = $row?->updated_at ? (string)$row->updated_at : null;

			return [
				'rank' => $rank,
				'points' => $points,
				'updated_at' => $updatedAt,
				'signature' => implode(':', [
					$rank ?? 'null',
					$points,
					$updatedAt ?? 'null',
				]),
			];
		}

		private function emitRetryDirective(): void
		{
			echo "retry: 60000\n\n";
			$this->flushStream();
		}

		private function emitHeartbeat(): void
		{
			echo ": heartbeat " . now()->timestamp . ' ' . str_repeat(' ', 1024) . "\n\n";
			$this->flushStream();
		}

		private function flushStream(): void
		{
			if (ob_get_level() > 0) {
				@ob_flush();
				@flush();
			}

			@flush();
		}
	}
