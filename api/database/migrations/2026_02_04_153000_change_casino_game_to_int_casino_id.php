<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::table('casino_game', function (Blueprint $table) {
			$table->string('int_casino_id')->nullable()->after('casino_id');
		});

		DB::statement('UPDATE casino_game cg JOIN casinos c ON cg.casino_id = c.id SET cg.int_casino_id = c.int_casino_id');

		Schema::table('casino_game', function (Blueprint $table) {
			$table->dropForeign(['casino_id']);
			$table->dropPrimary(['casino_id', 'game_id']);
		});

		Schema::table('casino_game', function (Blueprint $table) {
			$table->dropColumn('casino_id');
		});

		Schema::table('casino_game', function (Blueprint $table) {
			$table->string('int_casino_id')->nullable(false)->change();
			$table->primary(['game_id', 'int_casino_id']);
			$table->foreign('int_casino_id')
				->references('int_casino_id')
				->on('casinos')
				->cascadeOnDelete();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('casino_game', function (Blueprint $table) {
			$table->dropForeign(['int_casino_id']);
			$table->dropPrimary(['game_id', 'int_casino_id']);
		});

		Schema::table('casino_game', function (Blueprint $table) {
			$table->unsignedBigInteger('casino_id')->nullable()->after('game_id');
		});

		DB::statement('UPDATE casino_game cg JOIN casinos c ON cg.int_casino_id = c.int_casino_id SET cg.casino_id = c.id');

		Schema::table('casino_game', function (Blueprint $table) {
			$table->dropColumn('int_casino_id');
		});

		Schema::table('casino_game', function (Blueprint $table) {
			$table->unsignedBigInteger('casino_id')->nullable(false)->change();
			$table->primary(['casino_id', 'game_id']);
			$table->foreign('casino_id')
				->references('id')
				->on('casinos')
				->cascadeOnDelete();
		});
	}
};
