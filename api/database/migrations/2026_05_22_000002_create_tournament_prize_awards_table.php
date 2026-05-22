<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('tournament_prize_awards', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->uuid('tournament_id');
			$table->uuid('tournament_prize_id');
			$table->unsignedBigInteger('user_id');
			$table->unsignedBigInteger('points')->default(0);
			$table->unsignedInteger('draw_position')->default(1);
			$table->string('prize_name');
			$table->string('prize_currency')->nullable();
			$table->decimal('prize_amount', 18, 2)->default(0);
			$table->timestamp('approved_at')->nullable();
			$table->timestamps();

			$table->unique(['tournament_id', 'tournament_prize_id', 'draw_position']);
			$table->index(['tournament_id', 'user_id']);

			$table->foreign('tournament_id')
				->references('id')
				->on('tournaments')
				->cascadeOnDelete();
			$table->foreign('tournament_prize_id')
				->references('id')
				->on('tournament_prizes')
				->cascadeOnDelete();
			$table->foreign('user_id')
				->references('id')
				->on('players')
				->cascadeOnDelete();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('tournament_prize_awards');
	}
};
