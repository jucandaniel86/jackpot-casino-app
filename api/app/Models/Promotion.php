<?php

	namespace App\Models;

	use App\Traits\GenerateUniqueSlugTrait;
	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;

	class Promotion extends Model
	{
		use HasFactory, GenerateUniqueSlugTrait;

		protected $guarded = [];
		protected $appends = ['thumbnailUrl', 'bannerUrl'];

		protected $casts = [
			'seo' => 'object',
			'primaryAction' => 'object'
		];

		public function getThumbnailUrlAttribute()
		{
			return url(config('casino.uploads.promotions') . $this->thumbnail);
		}

		public function getBannerUrlAttribute()
		{
			return url(config('casino.uploads.promotions') . $this->banner);
		}
	}