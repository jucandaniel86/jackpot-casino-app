<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;

	class Transaction extends Model
	{
		use HasFactory;

		protected $table = 'transaction';
		protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];
		protected $fillable = [
			'wallet_id', 'uuid', 'currency', 'currency_id', 'currency_code', 'network',
			'type', 'status', 'amount_base', 'decimals', 'amount',
			'txid', 'from_address', 'to_address', 'meta', 'block_time', 'int_casino_id'
		];
	}