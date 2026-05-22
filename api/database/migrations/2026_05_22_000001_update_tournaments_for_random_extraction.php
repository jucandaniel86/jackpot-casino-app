<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table('tournaments', function (Blueprint $table) {
			$table->string('tournament_type')->default('DEFAULT')->after('point_rate');
			$table->integer('tournament_range')->default(-1)->after('tournament_type');
			$table->timestamp('random_prizes_allocated_at')->nullable()->after('tournament_range');

			$table->index('tournament_type');
			$table->index('random_prizes_allocated_at');
		});
	}

	public function down(): void
	{
		Schema::table('tournaments', function (Blueprint $table) {
			$table->dropIndex(['tournament_type']);
			$table->dropIndex(['random_prizes_allocated_at']);
			$table->dropColumn(['tournament_type', 'tournament_range', 'random_prizes_allocated_at']);
		});
	}
};
