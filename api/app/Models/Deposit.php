<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;

	class Deposit extends Model
	{
		use HasFactory;

		protected $fillable = ['wallet_id', 'currency', 'txid', 'to_address', 'amount_base', 'decimals', 'block_time', 'status'];
	}