<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;

	class JobRun extends Model
	{
		protected $table = 'job_runs';

		protected $fillable = [
			'uuid', 'job_class', 'job_name', 'queue', 'connection', 'status', 'attempt',
			'duration_ms', 'started_at', 'finished_at',
			'context', 'result', 'error_message', 'error_trace',
		];

		protected $casts = [
			'context' => 'array',
			'result' => 'array',
			'started_at' => 'datetime',
			'finished_at' => 'datetime',
		];
	}