<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;

	class Sliders extends Model
	{
		use HasFactory;

		protected $guarded = [];
		protected $appends = ['bannerUrl', 'bannerMobileUrl'];

		public function getBannerUrlAttribute()
		{
			if (!$this->banner) return '';

			return url(config('casino.uploads.sliders') . $this->banner);
		}

		public function getBannerMobileUrlAttribute()
		{
			if (!$this->banner_mobile) return '';

			return url(config('casino.uploads.sliders') . $this->banner_mobile);
		}

		public function page()
		{
			return $this->hasOne(Pages::class, 'id', 'page_id');
		}
	}