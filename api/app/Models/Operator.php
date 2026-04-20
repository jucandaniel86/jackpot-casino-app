<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Operator extends Model
{
  use HasFactory, LogsActivity;

	protected $fillable = ["name", "site_id", "loader_logo_path", "added_by"];

	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logOnly(['name', 'email', 'id']);
	}

	public function added(): HasOne {
		return $this->hasOne(User::class, 'id', 'added_by');
	}
}