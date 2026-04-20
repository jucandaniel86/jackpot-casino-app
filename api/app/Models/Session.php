<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\HasOne;
	use App\Models\Wallet;

	class Session extends Model
	{
		use HasFactory;

		protected $guarded = [];

		public function user(): HasOne
		{
			return $this->hasOne(Player::class, 'id', 'user_id');
		}

		public function game(): HasOne
		{
			return $this->hasOne(Game::class, 'id', 'game_id');
		}

		public function wallet(): HasOne
		{
			return $this->hasOne(Wallet::class, 'id', 'wallet_id');
		}
	}
