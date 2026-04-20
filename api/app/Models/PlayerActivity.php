<?php

	namespace App\Models;

	use App\Enums\PlayerActivityEnums;
	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;

	class PlayerActivity extends Model
	{
		use HasFactory;

		const UPDATED_AT = null;

		protected $table = "player_activity";

		protected $fillable = ['old', 'description', 'user_id', 'type', 'system', 'item_id', 'ip_address', 'user_agent',
			'country', 'city', 'os', 'device', 'browser'];

		protected $casts = [
			'type' => PlayerActivityEnums::class
		];

		public function user()
		{
			return $this->belongsTo(User::class, 'user_id');
		}
	}