<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;

	class WalletBalance extends Model
	{
		use HasFactory;

		protected $table = 'wallet_balances';
		public $incrementing = false;
		protected $primaryKey = 'wallet_id';

		protected $fillable = ['wallet_id', 'currency', 'available_base', 'reserved_base'];
	}