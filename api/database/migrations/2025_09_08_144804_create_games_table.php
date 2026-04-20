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
			Schema::create('games', function (Blueprint $table) {
				$table->id();
				$table->integer('game_id');
				$table->string('name');
				$table->text('description')->nullable();
				$table->string('thumbnail')->nullable();
				$table->string('slug');
				$table->text('iframe_url');
				$table->tinyInteger('is_fullpage')->nullable()->default(0);
				$table->tinyInteger('is_recommended')->nullable()->default(0);
				$table->tinyInteger('is_fun')->nullable()->default(0);
				$table->tinyInteger('active_on_site')->nullable()->default(0);
				$table->tinyInteger('soon')->nullable()->default(0);
				$table->integer('provider_id');
				$table->timestamps();
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('games');
		}
	};