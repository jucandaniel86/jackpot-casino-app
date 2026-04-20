<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListTournamentRequest;
use App\Http\Requests\StoreTournamentRequest;
use App\Http\Requests\UpdateTournamentRequest;
use App\Repositories\Tournaments;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

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
}

