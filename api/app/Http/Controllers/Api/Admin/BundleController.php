<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListBundleRequest;
use App\Http\Requests\StoreBundleRequest;
use App\Http\Requests\UpdateBundleRequest;
use App\Repositories\Bundles;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class BundleController extends Controller
{
	public function __construct(private Bundles $bundles)
	{
	}

	public function index(ListBundleRequest $request): JsonResponse
	{
		$data = $this->bundles->list($request->validated());

		return response()->json([
			'success' => true,
			'message' => 'Bundles fetched successfully.',
			'data' => $data,
		]);
	}

	public function show(string $id): JsonResponse
	{
		try {
			$data = $this->bundles->find($id);
		} catch (ModelNotFoundException) {
			return response()->json([
				'success' => false,
				'message' => 'Bundle not found.',
				'data' => null,
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Bundle fetched successfully.',
			'data' => $data,
		]);
	}

	public function store(StoreBundleRequest $request): JsonResponse
	{
		$data = $this->bundles->create($request->validated());

		return response()->json([
			'success' => true,
			'message' => 'Bundle created successfully.',
			'data' => $data,
		], 201);
	}

	public function update(UpdateBundleRequest $request, string $id): JsonResponse
	{
		try {
			$data = $this->bundles->update($id, $request->validated());
		} catch (ModelNotFoundException) {
			return response()->json([
				'success' => false,
				'message' => 'Bundle not found.',
				'data' => null,
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Bundle updated successfully.',
			'data' => $data,
		]);
	}

	public function destroy(string $id): JsonResponse
	{
		try {
			$this->bundles->delete($id);
		} catch (ModelNotFoundException) {
			return response()->json([
				'success' => false,
				'message' => 'Bundle not found.',
				'data' => null,
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Bundle deleted successfully.',
		]);
	}
}

