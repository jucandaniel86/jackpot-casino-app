<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TournamentPrize extends Model
{
	use HasUuids;

	protected $table = 'tournament_prizes';

	protected $fillable = [
		'tournament_id',
		'prize_name',
		'prize_type',
		'rank_from',
		'rank_to',
		'min_points',
		'prize_currency',
		'prize_amount',
		'metadata',
	];

	protected $casts = [
		'metadata' => 'array',
	];
}

