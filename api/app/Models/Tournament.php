<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

	protected $appends = [
		'thumbnail_url',
	];

	protected $casts = [
		'started_at' => 'datetime',
		'ended_at' => 'datetime',
	];

	public function getThumbnailUrlAttribute(): ?string
	{
		if (!$this->thumbnail) {
			return null;
		}

		return url(config('casino.uploads.tournaments') . $this->thumbnail);
	}

	public function games(): BelongsToMany
	{
		return $this->belongsToMany(
			Game::class,
			'tournament_games',
			'tournament_id',
			'game_id',
			'id',
			'game_id'
		)->withPivot(['id', 'tournament_id', 'game_id', 'created_at', 'updated_at'])
			->withTimestamps();
	}

	public function tournamentGames(): HasMany
	{
		return $this->hasMany(TournamentGame::class, 'tournament_id');
	}

	public function prizes(): HasMany
	{
		return $this->hasMany(TournamentPrize::class, 'tournament_id');
	}
}
