<?php

	namespace App\Repositories\System\Services;

	use App\Models\JobRun;
	use App\Repositories\System\Contracts\JobLogServiceInterface;
	use Illuminate\Support\Str;

	class JobLogService implements JobLogServiceInterface
	{
		public function start(string $jobClass, ?string $jobName, array $context = []): JobRun
		{
			return JobRun::create([
				'uuid' => (string)Str::uuid(),
				'job_class' => $jobClass,
				'job_name' => $jobName,
				'queue' => $this->queue(),
				'connection' => $this->connection(),
				'status' => 'running',
				'attempt' => $this->attempt(),
				'started_at' => now(),
				'context' => $context,
			]);
		}

		public function success(JobRun $run, array $result = []): void
		{
			$run->update([
				'status' => 'success',
				'finished_at' => now(),
				'duration_ms' => $this->durationMs($run),
				'result' => $result,
			]);
		}

		public function fail(JobRun $run, \Throwable $e, array $result = []): void
		{
			$run->update([
				'status' => 'failed',
				'finished_at' => now(),
				'duration_ms' => $this->durationMs($run),
				'result' => $result,
				'error_message' => $e->getMessage(),
				'error_trace' => $e->getTraceAsString(),
			]);
		}

		private function durationMs(JobRun $run): ?int
		{
			if (!$run->started_at) return null;
			return (int)now()->diffInMilliseconds($run->started_at);
		}

		private function connection(): ?string
		{
			return config('queue.default');
		}

		private function queue(): ?string
		{
			// dacă jobul e rulat cu queue:work --queue=crypto,default nu ai un singur nume aici.
			// păstrăm null sau "unknown" (poți seta manual în job context).
			return null;
		}

		private function attempt(): int
		{
			// În job ai $this->attempts() doar dacă folosești InteractsWithQueue.
			return 1;
		}
	}