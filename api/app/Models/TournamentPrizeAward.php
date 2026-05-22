<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TournamentPrizeAward extends Model
{
	protected $table = 'tournament_prize_awards';

	protected $guarded = [];

	protected $casts = [
		'approved_at' => 'datetime',
	];
}
