<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;

	class WithdrawRequest extends Model
	{
		protected $table = 'withdraw_requests';

		protected $fillable = [
			'uuid', 'wallet_id', 'player_id', 'currency', 'amount_base', 'decimals',
			'amount_ui', 'to_address', 'status', 'admin_note', 'reject_reason', 'txid',
			'completed_at', 'meta'
		];

		protected $casts = [
			'meta' => 'array',
		];

		public function wallet()
		{
			return $this->belongsTo(Wallet::class);
		}

		public function player()
		{
			return $this->belongsTo(Player::class, 'player_id');
		}
	}