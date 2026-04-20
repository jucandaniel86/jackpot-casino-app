<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerLoginVerificationCode extends Model
{
	protected $fillable = [
		'player_id',
		'login_token_hash',
		'code_hash',
		'attempts',
		'max_attempts',
		'ip_address',
		'user_agent_hash',
		'expires_at',
		'consumed_at',
	];

	protected $casts = [
		'expires_at' => 'datetime',
		'consumed_at' => 'datetime',
	];

	public function player(): BelongsTo
	{
		return $this->belongsTo(Player::class);
	}
}
