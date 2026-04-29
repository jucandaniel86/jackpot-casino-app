<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\HasMany;
	use Illuminate\Database\Eloquent\Relations\HasOne;
	use Illuminate\Database\Eloquent\Relations\MorphTo;

	class Wallet extends Model
	{
		protected $fillable = [
			'holder_type',
			'holder_id',
			'currency',
			'balance',
			'name',
			'wallet_type_id',
			'uuid',
			'currency_id',
			'currency_code'
		];
		protected $casts = ['meta' => 'array'];

		public function type(): HasOne
		{
			return $this->hasOne(WalletType::class, 'id', 'wallet_type_id');
		}

		public function holder(): MorphTo
		{
			return $this->morphTo();
		}

		public function transactions(): HasMany
		{
			return $this->hasMany(Transaction::class);
		}

		public function walletBalance(): HasOne
		{
			return $this->hasOne(WalletBalance::class, 'wallet_id', 'id');
		}

		public function getCurrencyIdAttribute($value)
		{
			return $value ?? $this->currency; // fallback
		}

		public function getCurrencyCodeAttribute($value)
		{
			if ($value) return $value;
			$c = (string)$this->currency;
			return str_contains($c, ':') ? explode(':', $c, 2)[1] : $c;
		}

		public function getNetworkAttribute($value)
		{
			if ($value) return $value;
			$c = (string)$this->currency;
			return str_contains($c, ':') ? explode(':', $c, 2)[0] : null;
		}
	}
