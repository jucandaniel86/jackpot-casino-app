<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;

	class PlayerLogin extends Model
	{
		use HasFactory;

		protected $guarded = [];
		protected $table = 'player_logins';

		protected $casts = [
			'ip_data' => 'array',
			'expires_at' => 'datetime',
		];
	}
