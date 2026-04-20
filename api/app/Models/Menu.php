<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\HasOne;

	class Menu extends Model
	{
		use HasFactory;

		public $timestamps = false;

		public function page(): HasOne
		{
			return $this->hasOne(Pages::class, 'id', 'page_id');
		}

		public function promotion(): HasOne
		{
			return $this->hasOne(Promotion::class, 'id', 'promotion_id');
		}

		public function game(): HasOne
		{
			return $this->hasOne(Game::class, 'id', 'game_id');
		}
	}