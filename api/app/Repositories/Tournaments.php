<?php

namespace App\Repositories;

use App\Models\Game;
use App\Models\Tournament;
use App\Models\TournamentGame;
use App\Models\TournamentPrize;
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
			->with(['tournamentGames.game', 'prizes'])
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
