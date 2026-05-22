<?php

namespace App\Repositories;

use App\Models\Game;
use App\Models\Tournament;
use App\Models\TournamentGame;
use App\Models\TournamentPrize;
use App\Models\TournamentPrizeAward;
use App\Traits\UploadFilesTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Tournaments
{
	use UploadFilesTrait;

	/**
	 * @param array{
	 *   status?: string|null,
	 *   is_active?: bool|null,
	 *   started_from?: string|null,
	 *   started_to?: string|null,
	 *   ended_from?: string|null,
	 *   ended_to?: string|null,
	 *   search?: string|null,
	 *   game_id?: string|null,
	 *   per_page?: int|null,
	 *   sort_by?: string|null,
	 *   sort_direction?: string|null
	 * } $filters
	 */
	public function list(array $filters = []): LengthAwarePaginator
	{
		$now = now();

		$query = Tournament::query()
			->with(['tournamentGames.game', 'prizes']);

		if (!empty($filters['status'])) {
			$query->where('status', (string)$filters['status']);
		}

		if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
			$isActive = filter_var($filters['is_active'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

			if ($isActive === true) {
				$query
					->where('status', 'active')
					->where('started_at', '<=', $now)
					->where('ended_at', '>=', $now);
			} elseif ($isActive === false) {
				$query->where(function ($q) use ($now) {
					$q->where('status', '!=', 'active')
						->orWhere('started_at', '>', $now)
						->orWhere('ended_at', '<', $now);
				});
			}
		}

		if (!empty($filters['started_from'])) {
			$query->where('started_at', '>=', Carbon::parse((string)$filters['started_from']));
		}
		if (!empty($filters['started_to'])) {
			$query->where('started_at', '<=', Carbon::parse((string)$filters['started_to']));
		}
		if (!empty($filters['ended_from'])) {
			$query->where('ended_at', '>=', Carbon::parse((string)$filters['ended_from']));
		}
		if (!empty($filters['ended_to'])) {
			$query->where('ended_at', '<=', Carbon::parse((string)$filters['ended_to']));
		}

		if (!empty($filters['search'])) {
			$search = (string)$filters['search'];
			$query->where('name', 'like', '%' . $search . '%');
		}

		if (!empty($filters['game_id'])) {
			$gameId = (string)$filters['game_id'];
			$query->whereHas('tournamentGames', function ($q) use ($gameId) {
				$q->where('game_id', $gameId);
			});
		}

		$sortBy = (string)($filters['sort_by'] ?? 'started_at');
		$sortDirection = strtolower((string)($filters['sort_direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

		$allowedSortBy = ['name', 'started_at', 'ended_at', 'status', 'created_at'];
		if (!in_array($sortBy, $allowedSortBy, true)) {
			$sortBy = 'started_at';
		}

		$query->orderBy($sortBy, $sortDirection);

		$perPage = (int)($filters['per_page'] ?? 20);
		if ($perPage <= 0) {
			$perPage = 20;
		}
		if ($perPage > 100) {
			$perPage = 100;
		}

		$paginator = $query->paginate($perPage);
		$paginator->getCollection()->transform(fn (Tournament $tournament) => $this->hydrateGamesRelation($tournament));

		return $paginator;
	}

	public function find(string $id): Tournament
	{
		$tournament = Tournament::query()
			->with(['tournamentGames.game', 'prizes', 'prizeAwards'])
			->find($id);

		if (!$tournament) {
			throw (new ModelNotFoundException())->setModel(Tournament::class, [$id]);
		}

		return $this->hydrateGamesRelation($tournament);
	}

	/**
	 * @param array{
	 *   name: string,
	 *   thumbnail?: string|null,
	 *   started_at: string,
	 *   ended_at: string,
	 *   status: string,
	 *   point_rate: int,
	 *   game_ids: array<int, string>,
	 *   prizes?: array<int, array{
	 *     prize_name: string,
	 *     prize_type: string,
	 *     rank_from?: int|null,
	 *     rank_to?: int|null,
	 *     min_points?: int|null,
	 *     prize_currency?: string|null,
	 *     prize_amount: string|int|float,
	 *     metadata?: array|null
	 *   }> | null
	 * } $data
	 */
	public function create(array $data): Tournament
	{
		return DB::transaction(function () use ($data) {
			$tournament = Tournament::query()->create([
				'name' => $data['name'],
				'thumbnail' => $data['thumbnail'] ?? null,
				'started_at' => $data['started_at'],
				'ended_at' => $data['ended_at'],
				'status' => $data['status'],
				'point_rate' => (int)$data['point_rate'],
				'tournament_type' => $data['tournament_type'] ?? 'DEFAULT',
				'tournament_range' => (int)($data['tournament_range'] ?? -1),
			]);

			$this->persistThumbnail($tournament, $data);

			$gameIds = $data['game_ids'] ?? [];
			foreach ($this->normalizeGameIds($gameIds) as $gameId) {
				TournamentGame::query()->create([
					'tournament_id' => $tournament->id,
					'game_id' => $gameId,
				]);
			}

			$prizes = $data['prizes'] ?? [];
			foreach ($prizes as $prize) {
				TournamentPrize::query()->create([
					'tournament_id' => $tournament->id,
					'prize_name' => $prize['prize_name'],
					'prize_type' => $prize['prize_type'] ?? 'rank',
					'rank_from' => $prize['rank_from'] ?? null,
					'rank_to' => $prize['rank_to'] ?? null,
					'min_points' => $prize['min_points'] ?? null,
					'prize_currency' => $prize['prize_currency'] ?? 'GC',
					'prize_amount' => $prize['prize_amount'] ?? 0,
					'metadata' => $prize['metadata'] ?? null,
				]);
			}

			return $this->find((string)$tournament->id);
		});
	}

	/**
	 * @param array{
	 *   name: string,
	 *   thumbnail?: string|null,
	 *   started_at: string,
	 *   ended_at: string,
	 *   status: string,
	 *   point_rate: int,
	 *   game_ids: array<int, string>,
	 *   prizes?: array<int, array{
	 *     prize_name: string,
	 *     prize_type: string,
	 *     rank_from?: int|null,
	 *     rank_to?: int|null,
	 *     min_points?: int|null,
	 *     prize_currency?: string|null,
	 *     prize_amount: string|int|float,
	 *     metadata?: array|null
	 *   }> | null
	 * } $data
	 */
	public function update(string $id, array $data): Tournament
	{
		return DB::transaction(function () use ($id, $data) {
			$tournament = $this->find($id);

			$tournament->fill([
				'name' => $data['name'],
				'thumbnail' => $data['thumbnail'] ?? null,
				'started_at' => $data['started_at'],
				'ended_at' => $data['ended_at'],
				'status' => $data['status'],
				'point_rate' => (int)$data['point_rate'],
				'tournament_type' => $data['tournament_type'] ?? 'DEFAULT',
				'tournament_range' => (int)($data['tournament_range'] ?? -1),
			]);
			$tournament->save();

			$this->persistThumbnail($tournament, $data);

			$tournament->tournamentGames()->delete();
			$gameIds = $data['game_ids'] ?? [];
			foreach ($this->normalizeGameIds($gameIds) as $gameId) {
				TournamentGame::query()->create([
					'tournament_id' => $tournament->id,
					'game_id' => $gameId,
				]);
			}

			$tournament->prizes()->delete();
			$prizes = $data['prizes'] ?? [];
			foreach ($prizes as $prize) {
				TournamentPrize::query()->create([
					'tournament_id' => $tournament->id,
					'prize_name' => $prize['prize_name'],
					'prize_type' => $prize['prize_type'] ?? 'rank',
					'rank_from' => $prize['rank_from'] ?? null,
					'rank_to' => $prize['rank_to'] ?? null,
					'min_points' => $prize['min_points'] ?? null,
					'prize_currency' => $prize['prize_currency'] ?? 'GC',
					'prize_amount' => $prize['prize_amount'] ?? 0,
					'metadata' => $prize['metadata'] ?? null,
				]);
			}

			return $this->find($id);
		});
	}

	public function delete(string $id): void
	{
		$tournament = $this->find($id);
		$this->deleteTournamentThumbnail($tournament);

		// Keep children rows intact for audit/history; the tournament is soft-deleted only.
		$tournament->delete();
	}

	public function clone(string $id): Tournament
	{
		return DB::transaction(function () use ($id) {
			$source = $this->find($id);

			$tournament = Tournament::query()->create([
				'name' => $source->name . ' (Clone)',
				'thumbnail' => null,
				'started_at' => $source->started_at,
				'ended_at' => $source->ended_at,
				'status' => 'draft',
				'point_rate' => (int)$source->point_rate,
				'tournament_type' => $source->tournament_type ?? 'DEFAULT',
				'tournament_range' => (int)($source->tournament_range ?? -1),
				'random_prizes_allocated_at' => null,
			]);

			foreach ($source->tournamentGames as $game) {
				TournamentGame::query()->create([
					'tournament_id' => $tournament->id,
					'game_id' => $game->game_id,
				]);
			}

			foreach ($source->prizes as $prize) {
				TournamentPrize::query()->create([
					'tournament_id' => $tournament->id,
					'prize_name' => $prize->prize_name,
					'prize_type' => $prize->prize_type,
					'rank_from' => $prize->rank_from,
					'rank_to' => $prize->rank_to,
					'min_points' => $prize->min_points,
					'prize_currency' => $prize->prize_currency,
					'prize_amount' => $prize->prize_amount,
					'metadata' => $prize->metadata,
				]);
			}

			return $this->find((string)$tournament->id);
		});
	}

	public function end(string $id): Tournament
	{
		$tournament = $this->find($id);
		$tournament->status = 'finished';
		$tournament->ended_at = now();
		$tournament->save();

		return $this->find($id);
	}

	public function eligibleRandomPlayers(string $id): array
	{
		$tournament = $this->find($id);
		$limit = (int)($tournament->tournament_range ?? -1);

		$query = DB::table('tournament_scores as ts')
			->join('players as p', 'p.id', '=', 'ts.user_id')
			->where('ts.tournament_id', $tournament->id)
			->where('ts.points', '>', 0)
			->orderByDesc('ts.points')
			->orderBy('ts.updated_at')
			->orderBy('ts.user_id');

		if ($limit > 0) {
			$query->limit($limit);
		}

		return $query
			->get([
				'ts.user_id as user_id',
				'ts.points as points',
				'p.username as username',
			])
			->map(fn ($row) => [
				'user_id' => (int)$row->user_id,
				'username' => (string)$row->username,
				'points' => (int)$row->points,
			])
			->values()
			->all();
	}

	public function randomExtraction(string $id): array
	{
		$tournament = $this->find($id);
		$players = $this->eligibleRandomPlayers($id);
		$prizeSlots = $this->randomPrizeSlots($tournament);
		$pool = $players;
		$awards = [];

		foreach ($prizeSlots as $slot) {
			if (count($pool) === 0) {
				break;
			}

			$winnerIndex = $this->weightedWinnerIndex($pool);
			$winner = $pool[$winnerIndex];
			array_splice($pool, $winnerIndex, 1);

			$awards[] = [
				'tournament_prize_id' => (string)$slot['tournament_prize_id'],
				'draw_position' => (int)$slot['draw_position'],
				'prize_name' => (string)$slot['prize_name'],
				'prize_currency' => $slot['prize_currency'],
				'prize_amount' => $slot['prize_amount'],
				'user_id' => (int)$winner['user_id'],
				'username' => (string)$winner['username'],
				'points' => (int)$winner['points'],
			];
		}

		return [
			'eligible_players' => $players,
			'awards' => $awards,
			'allocated' => $tournament->random_prizes_allocated_at !== null,
		];
	}

	public function approveRandomExtraction(string $id, array $awards): Tournament
	{
		return DB::transaction(function () use ($id, $awards) {
			$tournament = $this->find($id);

			if (($tournament->tournament_type ?? 'DEFAULT') !== 'RANDOM') {
				throw new \InvalidArgumentException('Only RANDOM tournaments can approve random extraction.');
			}

			if ($tournament->status !== 'finished') {
				throw new \InvalidArgumentException('Random extraction can be approved only after the tournament is finished.');
			}

			$eligibleByUserId = collect($this->eligibleRandomPlayers($id))->keyBy('user_id');
			$prizeIds = $tournament->prizes->pluck('id')->map(fn ($value) => (string)$value)->all();

			TournamentPrizeAward::query()
				->where('tournament_id', $tournament->id)
				->delete();

			foreach ($awards as $award) {
				if (!$eligibleByUserId->has((int)$award['user_id'])) {
					throw new \InvalidArgumentException('Award contains a non-eligible player.');
				}
				if (!in_array((string)$award['tournament_prize_id'], $prizeIds, true)) {
					throw new \InvalidArgumentException('Award contains an invalid prize.');
				}

				TournamentPrizeAward::query()->create([
					'tournament_id' => $tournament->id,
					'tournament_prize_id' => $award['tournament_prize_id'],
					'user_id' => (int)$award['user_id'],
					'points' => (int)($award['points'] ?? 0),
					'draw_position' => (int)($award['draw_position'] ?? 1),
					'prize_name' => $award['prize_name'],
					'prize_currency' => $award['prize_currency'] ?? null,
					'prize_amount' => $award['prize_amount'] ?? 0,
					'approved_at' => now(),
				]);
			}

			$tournament->random_prizes_allocated_at = now();
			$tournament->save();

			return $this->find($id);
		});
	}

	private function hydrateGamesRelation(Tournament $tournament): Tournament
	{
		$games = $tournament->tournamentGames
			->filter(fn (TournamentGame $tournamentGame) => $tournamentGame->game !== null)
			->map(function (TournamentGame $tournamentGame) use ($tournament) {
				$game = $tournamentGame->game;
				$game->setAttribute('tournament_id', (string)$tournament->id);
				$game->setAttribute('created_at', $tournamentGame->created_at);
				$game->setAttribute('updated_at', $tournamentGame->updated_at);

				return $game;
			})
			->values();

		$tournament->setRelation('games', $games);

		return $tournament;
	}

	/**
	 * Accept both games.id and games.game_id from admin/API input,
	 * but always persist the external game_id in tournament_games.
	 *
	 * @param array<int, string|int> $gameIds
	 * @return array<int, string>
	 */
	private function normalizeGameIds(array $gameIds): array
	{
		$normalized = [];

		foreach ($gameIds as $rawGameId) {
			$value = trim((string)$rawGameId);

			if ($value === '') {
				continue;
			}

			$game = Game::query()
				->where('game_id', $value)
				->orWhere('id', $value)
				->first();

			$normalized[] = (string)($game?->game_id ?? $value);
		}

		return array_values(array_unique($normalized));
	}

	private function randomPrizeSlots(Tournament $tournament): array
	{
		$slots = [];
		$drawPosition = 1;

		foreach ($tournament->prizes->sortBy(fn (TournamentPrize $prize) => (int)($prize->rank_from ?? 999999)) as $prize) {
			$slots[] = [
				'tournament_prize_id' => (string)$prize->id,
				'draw_position' => $drawPosition++,
				'prize_name' => (string)$prize->prize_name,
				'prize_currency' => $prize->prize_currency,
				'prize_amount' => $prize->prize_amount,
			];
		}

		return $slots;
	}

	private function weightedWinnerIndex(array $players): int
	{
		$total = array_sum(array_map(fn ($player) => max(1, (int)$player['points']), $players));
		$ticket = random_int(1, max(1, $total));
		$running = 0;

		foreach ($players as $index => $player) {
			$running += max(1, (int)$player['points']);
			if ($ticket <= $running) {
				return (int)$index;
			}
		}

		return max(0, count($players) - 1);
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function persistThumbnail(Tournament $tournament, array $data): void
	{
		if (!isset($data['thumbnail_file']) || $data['thumbnail_file'] === 'null' || $data['thumbnail_file'] === null) {
			return;
		}

		$path = config('casino.uploads.tournaments');
		$filePath = $path . $tournament->thumbnail;

		$thumbnail = $this->uploadThumbnail($data['thumbnail_file'], $path, $tournament->name, function () use ($filePath) {
			if (@is_file(public_path($filePath))) {
				@unlink(public_path($filePath));
			}
		});

		$tournament->thumbnail = $thumbnail;
		$tournament->save();
	}

	private function deleteTournamentThumbnail(Tournament $tournament): void
	{
		$filePath = config('casino.uploads.tournaments') . $tournament->thumbnail;

		if (@is_file(public_path($filePath))) {
			@unlink(public_path($filePath));
		}
	}
}
