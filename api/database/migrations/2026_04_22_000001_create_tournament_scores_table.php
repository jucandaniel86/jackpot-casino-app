<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('tournament_scores', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->uuid('tournament_id');
			$table->unsignedBigInteger('user_id');
			$table->unsignedBigInteger('points')->default(0);
			$table->timestamp('last_scored_at')->nullable();
			$table->timestamps();

			$table->unique(['tournament_id', 'user_id']);
			$table->index(['tournament_id', 'points']);
			$table->index('user_id');

			$table->foreign('tournament_id')
				->references('id')
				->on('tournaments')
				->cascadeOnDelete();
			$table->foreign('user_id')
				->references('id')
				->on('players')
				->cascadeOnDelete();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('tournament_scores');
	}
};

