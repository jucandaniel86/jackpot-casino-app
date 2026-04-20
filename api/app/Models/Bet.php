<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\HasOne;

	class Bet extends Model
	{
		use HasFactory;

		public $timestamps = false;
		protected $guarded = [];
		protected $casts = [
			'when_placed' => 'datetime',
		];
		
		public function user(): HasOne
		{
			return $this->hasOne(Player::class, 'id', 'user_id');
		}

		public function game(): HasOne
		{
			return $this->hasOne(Game::class, 'game_id', 'game_id');
		}


		public function wallet(): HasOne
		{
			return $this->hasOne(Wallet::class, 'id', 'wallet_id');
		}
	}