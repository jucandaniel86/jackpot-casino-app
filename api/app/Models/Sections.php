<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Concerns\HasUlids;
	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;
	use Ramsey\Uuid\Uuid;

	class Sections extends Model
	{
		use HasFactory, HasUlids;

		public $timestamps = false;
		protected $guarded = [];
		protected $primaryKey = 'id';

		protected $casts = [
			'resolutionConfig' => 'array',
			'data' => 'array'
		];

		protected static function boot()
		{
			parent::boot();

			static::creating(function (Model $model) {
				$model->setAttribute('id', Uuid::uuid4());
			});
		}

		public function page()
		{
			return $this->belongsToMany(Sections::class, 'page_sections',
				'section_id', 'page_id');
		}

		public function tags()
		{
			return $this->belongsToMany(Sections::class, 'tags_sections',
				'section_id', 'tag_id');
		}
	}