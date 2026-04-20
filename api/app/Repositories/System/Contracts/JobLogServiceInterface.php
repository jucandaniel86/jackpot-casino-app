<?php

	namespace App\Repositories\System\Contracts;

	use App\Models\JobRun;

	interface JobLogServiceInterface
	{
		public function start(string $jobClass, ?string $jobName, array $context = []): JobRun;

		public function success(JobRun $run, array $result = []): void;

		public function fail(JobRun $run, \Throwable $e, array $result = []): void;
	}