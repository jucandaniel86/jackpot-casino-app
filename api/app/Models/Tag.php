<?php

	namespace App\Models;

	use App\Traits\GenerateUniqueSlugTrait;
	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;

	class Tag extends Model
	{
		use HasFactory, GenerateUniqueSlugTrait;

		protected $guarded = [];

		public function sections()
		{
			return $this->belongsToMany(Sections::class, 'tags_sections',
				'tag_id', 'section_id');
		}

		public function page()
		{
			return $this->hasOne(Pages::class, 'id', 'page_id');
		}
	}