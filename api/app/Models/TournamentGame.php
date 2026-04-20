<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TournamentGame extends Model
{
	use HasUuids;

	protected $table = 'tournament_games';

	protected $fillable = [
		'tournament_id',
		'game_id',
	];
}

