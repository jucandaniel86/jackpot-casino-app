<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Relations\BelongsToMany;
	use Illuminate\Database\Eloquent\Relations\HasMany;
	use Illuminate\Database\Eloquent\Relations\HasOne;
	use Illuminate\Foundation\Auth\User as Authenticatable;
	use Laravel\Sanctum\HasApiTokens;
	use Illuminate\Notifications\Notifiable;
	use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

	class Player extends Authenticatable implements JWTSubject
	{
		use HasFactory, HasApiTokens, Notifiable;

		protected $fillable = ['password', 'username', 'email', 'active', 'casino_id', 'wallet_id', 'fixed_id', 'int_casino_id'];
		protected $guarded = [];

		public function getJWTIdentifier()
		{
			return $this->getKey();
		}

		public function getJWTCustomClaims()
		{
			return [
				'email' => $this->email,
				'name' => $this->name
			];
		}

		public function profile(): HasOne
		{
			return $this->hasOne(PlayerProfile::class, 'player_id', 'id');
		}

		public function sessions(): HasMany
		{
			return $this->hasMany(Session::class, 'user_id', 'id');
		}

		public function favorites(): BelongsToMany
		{
			return $this->belongsToMany(Game::class, 'player_games', 'player_id', 'game_id');
		}

		public function wallets()
		{
			return $this->morphMany(Wallet::class, 'holder');
		}

		public function currentWallet()
		{
			return $this->hasOne(Wallet::class, 'id', 'current_wallet_id');
		}
	}