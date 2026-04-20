<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;

	class Providers extends Model
	{
		use HasFactory;

		protected $appends = ['thumbnail_url'];

		public function casinos()
		{
			return $this->belongsToMany(Casino::class, 'casino_provider',
				'provider_id', 'casino_id');
		}

		public function getThumbnailUrlAttribute()
		{
			return url(config('casino.uploads.providers') . $this->thumbnail);
		}
	}
