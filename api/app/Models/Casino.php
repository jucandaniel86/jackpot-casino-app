<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;

	class Casino extends Model
	{
		use HasFactory;

		protected $fillable = ['internal_casino_id', 'name', 'logo', 'casino_url', 'active', 'meta'];
		protected $appends = ['logo_absolute_path'];

		protected $casts = ['meta' => 'array', 'created_at' => 'datetime', 'casino_apis_url' => 'array'];

		public function getLogoAbsolutePathAttribute(): string
		{
			return url('/casinos/' . $this->logo);
		}

		public function games()
		{
			return $this->belongsToMany(Game::class, 'casino_game',
				'casino_id', 'game_id');
		}

		public function providers()
		{
			return $this->belongsToMany(Providers::class, 'casino_provider',
				'casino_id', 'provider_id');
		}

		public function wallets()
		{
			return $this->morphMany(Wallet::class, 'holder');
		}
	}