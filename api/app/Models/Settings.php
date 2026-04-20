<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;
	use Spatie\Activitylog\LogOptions;
	use Spatie\Activitylog\Traits\LogsActivity;

	class Settings extends Model
	{
		use HasFactory, LogsActivity;

		protected $fillable = ["setting_key", "setting_value", "setting_type", "operator_id"];

		public $timestamps = false;

		public function getActivitylogOptions(): LogOptions
		{
			return LogOptions::defaults()
				->logOnly(['name', 'email', 'id']);
		}
	}