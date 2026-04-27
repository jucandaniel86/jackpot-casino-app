<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('tournament_score_events', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->uuid('tournament_id');
			$table->unsignedBigInteger('bet_id');
			$table->uuid('bet_transaction_id')->nullable();
			$table->unsignedBigInteger('user_id');
			$table->integer('delta_points');
			$table->timestamp('occurred_at')->nullable();
			$table->timestamps();

			$table->unique(['tournament_id', 'bet_id']);
			$table->index(['tournament_id', 'occurred_at']);
			$table->index('user_id');

			$table->foreign('tournament_id')
				->references('id')
				->on('tournaments')
				->cascadeOnDelete();
			$table->foreign('bet_id')
				->references('id')
				->on('bets')
				->cascadeOnDelete();
			$table->foreign('user_id')
				->references('id')
				->on('players')
				->cascadeOnDelete();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('tournament_score_events');
	}
};

