<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;

	class TournamentScoreEvent extends Model
	{
		protected $table = 'tournament_score_events';

		protected $guarded = [];

		protected $casts = [
			'occurred_at' => 'datetime',
		];
	}

