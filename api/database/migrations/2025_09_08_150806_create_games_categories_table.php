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
			Schema::create('games_categories', function (Blueprint $table) {
				$table->unsignedBigInteger('game_id');
				$table->unsignedBigInteger('category_id');
				$table->primary(['game_id', 'category_id']);

				$table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
				$table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('games_categories');
		}
	};