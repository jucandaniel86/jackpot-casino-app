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
			Schema::table('player_activity', function (Blueprint $table) {
				$table->string('country')->nullable();
				$table->string('city')->nullable();
				$table->string('os')->nullable();
				$table->string('device')->nullable();
				$table->string('browser')->nullable();
				$table->dateTime('created_at')->nullable();
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::table('player_activity', function (Blueprint $table) {
				//
			});
		}
	};