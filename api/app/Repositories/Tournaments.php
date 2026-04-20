<?php

namespace App\Repositories;

use App\Models\Tournament;
use App\Models\TournamentGame;
use App\Models\TournamentPrize;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Tournaments
{
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
			->with(['games', 'prizes']);

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
			$query->whereHas('games', function ($q) use ($gameId) {
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

		return $query->paginate($perPage);
	}

	public function find(string $id): Tournament
	{
		$tournament = Tournament::query()
			->with(['games', 'prizes'])
			->find($id);

		if (!$tournament) {
			throw (new ModelNotFoundException())->setModel(Tournament::class, [$id]);
		}

		return $tournament;
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

			$gameIds = $data['game_ids'] ?? [];
			foreach ($gameIds as $gameId) {
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

			$tournament->games()->delete();
			$gameIds = $data['game_ids'] ?? [];
			foreach ($gameIds as $gameId) {
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

		// Keep children rows intact for audit/history; the tournament is soft-deleted only.
		$tournament->delete();
	}
}

