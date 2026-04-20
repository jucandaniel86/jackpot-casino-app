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
			Schema::create('player_activity', function (Blueprint $table) {
				$table->id();
				$table->string('old')->nullable();
				$table->text('description')->nullable();
				$table->integer('user_id');
				$table->string('type')->nullable();
				$table->string('system')->nullable();
				$table->integer('item_id')->nullable();
				$table->string('ip_address')->nullable();
				$table->string('user_agent')->nullable();
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('player_activity');
		}
	};