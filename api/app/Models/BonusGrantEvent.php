<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonusGrantEvent extends Model
{
	protected $fillable = [
		'bonus_grant_id',
		'event_type',
		'amount_base',
		'idempotency_key',
		'reference_type',
		'reference_id',
		'meta',
	];

	protected $casts = [
		'meta' => 'array',
	];
}
