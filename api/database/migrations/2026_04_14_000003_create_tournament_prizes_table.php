<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('tournament_prizes', function (Blueprint $table) {
			$table->uuid('id')->primary();
			$table->uuid('tournament_id');
			$table->string('prize_name');
			$table->string('prize_type')->default('rank');
			$table->integer('rank_from')->nullable();
			$table->integer('rank_to')->nullable();
			$table->bigInteger('min_points')->nullable();
			$table->string('prize_currency')->default('GC');
			$table->decimal('prize_amount', 18, 2)->default(0);
			$table->json('metadata')->nullable();
			$table->timestamps();

			$table->foreign('tournament_id')->references('id')->on('tournaments');
			$table->index('tournament_id');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('tournament_prizes');
	}
};

