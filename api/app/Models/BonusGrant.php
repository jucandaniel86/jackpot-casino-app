<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonusGrant extends Model
{
	protected $fillable = [
		'bonus_rule_id',
		'int_casino_id',
		'player_id',
		'wallet_id_bonus',
		'currency_id',
		'currency_code',
		'amount_granted_base',
		'amount_remaining_base',
		'status',
		'source_type',
		'source_ref',
		'expires_at',
		'wagering_required_base',
		'wagering_progress_base',
		'real_wager_required_base',
		'real_wager_progress_base',
		'max_convert_to_real_base',
		'converted_to_real_base',
		'withdraw_lock',
		'meta',
	];

	protected $casts = [
		'meta' => 'array',
		'expires_at' => 'datetime',
	];
}
