<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model
{
    protected $guarded = [];

    protected $appends = ['thumbnailUrl'];

    protected $casts = [
        'rule' => 'array',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function claims(): HasMany
    {
        return $this->hasMany(RewardClaim::class);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail) {
            return null;
        }

        return url(config('casino.uploads.rewards', '/uploads/rewards/') . $this->thumbnail);
    }
}
