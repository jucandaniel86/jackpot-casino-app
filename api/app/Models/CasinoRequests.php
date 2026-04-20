<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;

	class CasinoRequests extends Model
	{
		use HasFactory;

		protected $table = "casino_api_requests";
		protected $guarded = [];

		public $timestamps = false;
	}