<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;

	class TournamentScore extends Model
	{
		protected $table = 'tournament_scores';

		protected $guarded = [];

		protected $casts = [
			'last_scored_at' => 'datetime',
		];
	}

