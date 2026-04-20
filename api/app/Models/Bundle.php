<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bundle extends Model
{
	use HasUuids;
	use SoftDeletes;

	protected $fillable = [
		'name',
		'slug',
		'short_description',
		'description',
		'price_amount',
		'price_currency',
		'gc_amount',
		'coin_amount',
		'thumbnail',
		'icon',
		'label',
		'subtitle',
		'cta_text',
		'badge_text',
		'badge_color',
		'border_color',
		'accent_color',
		'background_color',
		'text_color',
		'ribbon_text',
		'tag',
		'tag_color',
		'image_url',
		'is_active',
		'is_featured',
		'is_popular',
		'sort_order',
		'metadata',
		'starts_at',
		'ends_at',
	];

	protected $casts = [
		'is_active' => 'boolean',
		'is_featured' => 'boolean',
		'is_popular' => 'boolean',
		'price_amount' => 'decimal:2',
		'gc_amount' => 'decimal:2',
		'coin_amount' => 'decimal:2',
		'metadata' => 'array',
		'starts_at' => 'datetime',
		'ends_at' => 'datetime',
	];
}
