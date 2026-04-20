<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;

	class ProviderIdempotency extends Model
	{
		use HasFactory;

		protected $fillable = ['provider', 'key', 'endpoint', 'http_status', 'response_json'];
		protected $casts = ['response_json' => 'array'];
	}