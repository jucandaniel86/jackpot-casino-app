<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;

	class Login extends Model
	{
		use HasFactory;

		protected $table = "player_logins";

		protected $fillable = ["device_type", "browser", "city", "expires_at", "country", "platform", "ip", "user_agent", "authenticatable_type", "authenticatable_id"];
	}