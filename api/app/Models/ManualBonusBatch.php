<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualBonusBatch extends Model
{
	protected $fillable = [
		'int_casino_id',
		'name',
		'segment_filter_json',
		'status',
		'estimated_players',
		'created_by',
	];

	protected $casts = [
		'segment_filter_json' => 'array',
	];
}
