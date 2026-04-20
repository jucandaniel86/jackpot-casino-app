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
			Schema::create('player_logins', function (Blueprint $table) {
				$table->id();
				$table->integer('user_id');
				$table->tinyInteger('logged_out');
				$table->dateTime('logged_in_at');
				$table->dateTime('logged_out_at');
				$table->string('ip_address');
				$table->string('token_id');
				$table->string('token_secret');
				$table->tinyInteger('token_deleted')->nullable()->default(0);
				$table->string('device');
				$table->timestamps();
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('player_logins');
		}
	};