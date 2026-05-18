<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardClaim extends Model
{
    protected $fillable = [
        'player_id',
        'wallet_id',
        'int_casino_id',
        'email',
        'reward_type',
        'amount_base',
        'amount',
        'decimals',
        'currency_id',
        'currency_code',
        'status',
        'meta',
        'claimed_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'claimed_at' => 'datetime',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
