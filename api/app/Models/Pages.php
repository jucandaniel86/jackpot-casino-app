<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;

	class Pages extends Model
	{
		use HasFactory;

		protected $guarded = [];
		protected $casts = [
			'seo' => 'array',
		];

		public function sections()
		{
			return $this->belongsToMany(Sections::class, 'page_sections',
				'page_id', 'section_id');
		}

		public function page()
		{
			return $this->belongsToMany(Sections::class, 'page_sections',
				'section_id', 'page_id');
		}
	}