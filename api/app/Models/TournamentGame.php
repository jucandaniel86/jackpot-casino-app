<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentGame extends Model
{
	use HasUuids;

	protected $table = 'tournament_games';

	protected $fillable = [
		'tournament_id',
		'game_id',
	];

	public function tournament(): BelongsTo
	{
		return $this->belongsTo(Tournament::class, 'tournament_id');
	}

	public function game(): BelongsTo
	{
		return $this->belongsTo(Game::class, 'game_id', 'game_id');
	}
}
