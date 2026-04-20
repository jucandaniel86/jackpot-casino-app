<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('tournament_games', function (Blueprint $table) {
			$table->uuid('id')->primary();
			$table->uuid('tournament_id');
			$table->uuid('game_id');
			$table->timestamps();

			$table->foreign('tournament_id')->references('id')->on('tournaments');

			$table->unique(['tournament_id', 'game_id']);
			$table->index('tournament_id');
			$table->index('game_id');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('tournament_games');
	}
};

