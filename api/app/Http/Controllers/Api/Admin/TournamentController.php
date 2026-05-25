<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Enums\TournamentType;
use App\Http\Requests\ListTournamentRequest;
use App\Http\Requests\StoreTournamentRequest;
use App\Http\Requests\UpdateTournamentRequest;
use App\Repositories\Tournaments;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TournamentController extends Controller
{
	public function __construct(private Tournaments $tournaments)
	{
	}

	public function index(ListTournamentRequest $request): JsonResponse
	{
		$data = $this->tournaments->list($request->validated());

		return response()->json([
			'success' => true,
			'message' => 'Tournaments fetched successfully.',
			'data' => $data,
		]);
	}

	public function types(): JsonResponse
	{
		return response()->json([
			'success' => true,
			'message' => 'Tournament types fetched successfully.',
			'data' => TournamentType::options(),
		]);
	}

	public function show(string $id): JsonResponse
	{
		try {
			$data = $this->tournaments->find($id);
		} catch (ModelNotFoundException) {
			return response()->json([
				'success' => false,
				'message' => 'Tournament not found.',
				'data' => null,
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Tournament fetched successfully.',
			'data' => $data,
		]);
	}

	public function store(StoreTournamentRequest $request): JsonResponse
	{
		$data = $this->tournaments->create($request->validated());

		return response()->json([
			'success' => true,
			'message' => 'Tournament created successfully.',
			'data' => $data,
		], 201);
	}

	public function update(UpdateTournamentRequest $request, string $id): JsonResponse
	{
		try {
			$data = $this->tournaments->update($id, $request->validated());
		} catch (ModelNotFoundException) {
			return response()->json([
				'success' => false,
				'message' => 'Tournament not found.',
				'data' => null,
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Tournament updated successfully.',
			'data' => $data,
		]);
	}

	public function destroy(string $id): JsonResponse
	{
		try {
			$this->tournaments->delete($id);
		} catch (ModelNotFoundException) {
			return response()->json([
				'success' => false,
				'message' => 'Tournament not found.',
				'data' => null,
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Tournament deleted successfully.',
		]);
	}

	public function clone(string $id): JsonResponse
	{
		try {
			$data = $this->tournaments->clone($id);
		} catch (ModelNotFoundException) {
			return response()->json([
				'success' => false,
				'message' => 'Tournament not found.',
				'data' => null,
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Tournament cloned successfully.',
			'data' => $data,
		], 201);
	}

	public function end(string $id): JsonResponse
	{
		try {
			$data = $this->tournaments->end($id);
		} catch (ModelNotFoundException) {
			return response()->json([
				'success' => false,
				'message' => 'Tournament not found.',
				'data' => null,
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Tournament ended successfully.',
			'data' => $data,
		]);
	}

	public function randomExtraction(string $id): JsonResponse
	{
		try {
			$data = $this->tournaments->randomExtraction($id);
		} catch (ModelNotFoundException) {
			return response()->json([
				'success' => false,
				'message' => 'Tournament not found.',
				'data' => null,
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Random extraction completed.',
			'data' => $data,
		]);
	}

	public function randomExtractionEligible(string $id): JsonResponse
	{
		try {
			$tournament = $this->tournaments->find($id);
			$data = [
				'eligible_players' => $this->tournaments->eligibleRandomPlayers($id),
				'awards' => [],
				'allocated' => $tournament->random_prizes_allocated_at !== null,
			];
		} catch (ModelNotFoundException) {
			return response()->json([
				'success' => false,
				'message' => 'Tournament not found.',
				'data' => null,
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Eligible players fetched successfully.',
			'data' => $data,
		]);
	}

	public function exportChanceList(string $id): JsonResponse|StreamedResponse
	{
		try {
			$tournament = $this->tournaments->find($id);
		} catch (ModelNotFoundException) {
			return response()->json([
				'success' => false,
				'message' => 'Tournament not found.',
				'data' => null,
			], 404);
		}

		if ($tournament->status !== 'finished') {
			return response()->json([
				'success' => false,
				'message' => 'Chance list can be exported only for finished tournaments.',
				'data' => null,
			], 422);
		}

		$players = $this->tournaments->eligibleRandomPlayers($id);
		$filename = 'tournament-' . $tournament->id . '-chance-list.csv';

		return response()->streamDownload(function () use ($players) {
			$handle = fopen('php://output', 'w');

			foreach ($players as $player) {
				$chances = max(0, (int)$player['points']);
				$username = trim((string)($player['username'] ?? ''));
				if ($username === '') {
					continue;
				}

				for ($i = 0; $i < $chances; $i++) {
					fputcsv($handle, [$username]);
				}
			}

			fclose($handle);
		}, $filename, [
			'Content-Type' => 'text/csv; charset=UTF-8',
		]);
	}

	public function approveRandomExtraction(Request $request, string $id): JsonResponse
	{
		$validated = $request->validate([
			'awards' => 'required|array|min:1',
			'awards.*.tournament_prize_id' => 'required|uuid',
			'awards.*.draw_position' => 'required|integer|min:1',
			'awards.*.prize_name' => 'required|string|max:255',
			'awards.*.prize_currency' => 'nullable|string|max:20',
			'awards.*.prize_amount' => 'required|numeric|min:0',
			'awards.*.user_id' => 'required|integer|min:1',
			'awards.*.points' => 'required|integer|min:0',
		]);

		try {
			$data = $this->tournaments->approveRandomExtraction($id, $validated['awards']);
		} catch (ModelNotFoundException) {
			return response()->json([
				'success' => false,
				'message' => 'Tournament not found.',
				'data' => null,
			], 404);
		} catch (\InvalidArgumentException $e) {
			return response()->json([
				'success' => false,
				'message' => $e->getMessage(),
				'data' => null,
			], 422);
		}

		return response()->json([
			'success' => true,
			'message' => 'Random extraction approved successfully.',
			'data' => $data,
		]);
	}
}
