<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;

	class WalletLedgerEntry extends Model
	{
		use HasFactory;

		protected $table = 'wallet_ledger_entries';
		protected $casts = ['meta' => 'array'];

		protected $fillable = [
			'wallet_id', 'currency', 'type', 'direction', 'amount_base', 'decimals',
			'reference_type', 'reference_id', 'idempotency_key', 'meta'
		];
	}