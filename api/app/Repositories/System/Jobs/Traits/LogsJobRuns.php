<?php

	namespace App\Repositories\System\Jobs\Traits;

	use App\Repositories\System\Contracts\JobLogServiceInterface;
	use App\Models\JobRun;

	trait LogsJobRuns
	{
		protected ?JobRun $jobRun = null;

		protected function jobLogStart(?string $name, array $context = []): void
		{
			$svc = app(JobLogServiceInterface::class);
			$this->jobRun = $svc->start(static::class, $name, $context);
		}

		protected function jobLogSuccess(array $result = []): void
		{
			if (!$this->jobRun) return;
			app(JobLogServiceInterface::class)->success($this->jobRun, $result);
		}

		protected function jobLogFail(\Throwable $e, array $result = []): void
		{
			if (!$this->jobRun) return;
			app(JobLogServiceInterface::class)->fail($this->jobRun, $e, $result);
		}
	}