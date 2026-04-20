<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tournament extends Model
{
	use HasUuids;
	use SoftDeletes;

	protected $fillable = [
		'name',
		'thumbnail',
		'started_at',
		'ended_at',
		'status',
		'point_rate',
	];

	protected $casts = [
		'started_at' => 'datetime',
		'ended_at' => 'datetime',
	];

	public function games(): HasMany
	{
		return $this->hasMany(TournamentGame::class, 'tournament_id');
	}

	public function prizes(): HasMany
	{
		return $this->hasMany(TournamentPrize::class, 'tournament_id');
	}
}

