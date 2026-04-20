<?php

	namespace App\Http\Controllers\Api;

	use App\Http\Controllers\Controller;
	use Illuminate\Http\Request;
	use App\Models\JobRun;

	class JobRunsController extends Controller
	{
		public function index(Request $request)
		{
			$data = $request->validate([
				'status' => ['nullable', 'in:running,success,failed'],
				'job_name' => ['nullable', 'string', 'max:100'],
				'from' => ['nullable', 'date'],
				'to' => ['nullable', 'date', 'after:from'],
				'page' => ['nullable', 'integer', 'min:1'],
				'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
			]);

			$q = JobRun::query()
				->when($data['status'] ?? null, fn($qq, $v) => $qq->where('status', $v))
				->when($data['job_name'] ?? null, fn($qq, $v) => $qq->where('job_name', $v))
				->when($data['from'] ?? null, fn($qq, $v) => $qq->where('started_at', '>=', $v))
				->when($data['to'] ?? null, fn($qq, $v) => $qq->where('started_at', '<', $v))
				->orderByDesc('id');

			return response()->json([
				'status' => 'success',
				'result' => $q->paginate((int)($data['per_page'] ?? 50)),
			]);
		}

		public function show(string $uuid)
		{
			$run = JobRun::query()->where('uuid', $uuid)->firstOrFail();
			return response()->json(['status' => 'success', 'result' => $run]);
		}
	}