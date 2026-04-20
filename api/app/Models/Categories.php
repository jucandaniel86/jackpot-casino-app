<?php

	namespace App\Models;

	use App\Traits\GenerateUniqueSlugTrait;
	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;

	class Categories extends Model
	{
		use HasFactory, GenerateUniqueSlugTrait;

		protected $casts = [
			'seo' => 'array',
		];

		protected $guarded = [];

		public function parent()
		{
			return $this->belongsTo(Categories::class, 'parent_id');
		}

		public function children()
		{
			return $this->hasMany(Categories::class, 'parent_id');
		}

		public function descendants()
		{
			return $this->children()->with('descendants');
		}
	}