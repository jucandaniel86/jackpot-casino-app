<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		/**
		 * Run the migrations.
		 */
		public function up(): void
		{
			Schema::table('player_logins', function (Blueprint $table) {
				$table->id();
				$table->timestamps();
				$table->string('authenticatable_type');
				$table->unsignedBigInteger('authenticatable_id');
				$table->string('user_agent');
				$table->string('ip');
				$table->longText('ip_data')->nullable();
				$table->string('device_type')->nullable();
				$table->string('device')->nullable();
				$table->string('platform')->nullable();
				$table->string('browser')->nullable();
				$table->string('city')->nullable();
				$table->string('region')->nullable();
				$table->string('country')->nullable();
				$table->string('session_id');
				$table->string('remember_token');
				$table->string('oauth_access_token_id');
				$table->unsignedBigInteger('personal_access_token_id');
				$table->timestamp('expires_at');
				$table->timestamp('deleted_at');
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::table('player_logins', function (Blueprint $table) {
				//
			});
		}
	};