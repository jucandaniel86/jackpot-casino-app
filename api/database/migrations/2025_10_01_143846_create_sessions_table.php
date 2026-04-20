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
			Schema::create('sessions', function (Blueprint $table) {
				$table->id();
				$table->uuid('session');
				$table->integer('user_id')->nullable();
				$table->integer('game_id');
				$table->integer('wallet_id')->nullable();
				$table->decimal('start_balance', 19, 4)->default(0);
				$table->tinyInteger('demo')->default(0)->nullable();
				$table->string('ip_address', 45)->nullable();
				$table->text('user_agent')->nullable();
				$table->timestamp('expire_at')->nullable();
				$table->timestamps();
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('sessions');
		}
	};