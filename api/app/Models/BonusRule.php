<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonusRule extends Model
{
	protected $fillable = [
		'int_casino_id',
		'name',
		'trigger_type',
		'campaign_type',
		'condition_json',
		'reward_type',
		'reward_value',
		'currency_id',
		'currency_code',
		'max_reward_amount',
		'deposit_bonus_multiplier',
		'wagering_multiplier',
		'real_wager_multiplier',
		'bonus_wager_multiplier',
		'consume_priority',
		'win_destination',
		'max_convert_to_real_ui',
		'expire_after_days',
		'valid_from',
		'valid_until',
		'priority',
		'stacking_policy',
		'is_active',
		'created_by',
		'updated_by',
	];

	protected $casts = [
		'condition_json' => 'array',
		'is_active' => 'boolean',
		'valid_from' => 'datetime',
		'valid_until' => 'datetime',
	];
}
