<?php

	namespace App\Models;

	use App\Traits\GenerateUniqueSlugTrait;
	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;

	class Game extends Model
	{
		use HasFactory, GenerateUniqueSlugTrait;

		protected $guarded = [];
		protected $appends = ['thumbnail_url'];

		public function categories()
		{
			return $this->belongsToMany(Categories::class, 'games_categories',
				'game_id', 'category_id');
		}

		public function provider()
		{
			return $this->hasOne(Providers::class, 'id', 'provider_id');
		}

		public function casinos()
		{
			return $this->belongsToMany(Casino::class, 'casino_game',
				'game_id', 'int_casino_id', 'id', 'int_casino_id');
		}

		public function getThumbnailUrlAttribute()
		{
			return url(config('casino.uploads.games') . $this->thumbnail);
		}


	}
