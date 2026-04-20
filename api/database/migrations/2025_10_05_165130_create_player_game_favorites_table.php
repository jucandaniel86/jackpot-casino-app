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
			Schema::create('player_games', function (Blueprint $table) {
				$table->unsignedBigInteger('player_id');
				$table->unsignedBigInteger('game_id');
				$table->primary(['player_id', 'game_id']);

				$table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
				$table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('player_games');
		}
	};