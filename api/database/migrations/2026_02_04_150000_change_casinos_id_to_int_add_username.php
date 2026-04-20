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
		Schema::table('casinos', function (Blueprint $table) {
			$table->unsignedBigInteger('id_int')->nullable()->after('id');
			$table->string('username')->nullable()->after('int_casino_id');
		});

		Schema::table('casino_game', function (Blueprint $table) {
			$table->unsignedBigInteger('casino_id_int')->nullable()->after('casino_id');
		});

		Schema::table('casino_provider', function (Blueprint $table) {
			$table->unsignedBigInteger('casino_id_int')->nullable()->after('casino_id');
		});

		DB::statement('SET @i := 0');
		DB::statement('UPDATE casinos SET id_int = (@i := @i + 1) ORDER BY created_at, id');

		DB::statement('UPDATE casino_game cg JOIN casinos c ON cg.casino_id = c.id SET cg.casino_id_int = c.id_int');
		DB::statement('UPDATE casino_provider cp JOIN casinos c ON cp.casino_id = c.id SET cp.casino_id_int = c.id_int');

		Schema::table('casino_game', function (Blueprint $table) {
			$table->dropForeign(['casino_id']);
			$table->dropPrimary(['casino_id', 'game_id']);
		});

		Schema::table('casino_provider', function (Blueprint $table) {
			$table->dropForeign(['casino_id']);
			$table->dropPrimary(['casino_id', 'provider_id']);
		});

		Schema::table('casino_game', function (Blueprint $table) {
			$table->dropColumn('casino_id');
		});

		Schema::table('casino_provider', function (Blueprint $table) {
			$table->dropColumn('casino_id');
		});

		DB::statement('ALTER TABLE casino_game CHANGE casino_id_int casino_id BIGINT UNSIGNED NOT NULL');
		DB::statement('ALTER TABLE casino_provider CHANGE casino_id_int casino_id BIGINT UNSIGNED NOT NULL');

		Schema::table('casino_game', function (Blueprint $table) {
			$table->primary(['casino_id', 'game_id']);
			$table->foreign('casino_id')->references('id')->on('casinos')->cascadeOnDelete();
		});

		Schema::table('casino_provider', function (Blueprint $table) {
			$table->primary(['casino_id', 'provider_id']);
			$table->foreign('casino_id')->references('id')->on('casinos')->cascadeOnDelete();
		});

		DB::statement('ALTER TABLE casinos DROP PRIMARY KEY');
		DB::statement('ALTER TABLE casinos CHANGE id uuid CHAR(36) NOT NULL');
		DB::statement('ALTER TABLE casinos CHANGE id_int id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
		DB::statement('ALTER TABLE casinos ADD PRIMARY KEY (id)');
		DB::statement('ALTER TABLE casinos ADD UNIQUE KEY casinos_uuid_unique (uuid)');
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('casino_game', function (Blueprint $table) {
			$table->dropForeign(['casino_id']);
			$table->dropPrimary(['casino_id', 'game_id']);
		});

		Schema::table('casino_provider', function (Blueprint $table) {
			$table->dropForeign(['casino_id']);
			$table->dropPrimary(['casino_id', 'provider_id']);
		});

		Schema::table('casino_game', function (Blueprint $table) {
			$table->uuid('casino_id_uuid')->nullable()->after('casino_id');
		});

		Schema::table('casino_provider', function (Blueprint $table) {
			$table->uuid('casino_id_uuid')->nullable()->after('casino_id');
		});

		DB::statement('UPDATE casino_game cg JOIN casinos c ON cg.casino_id = c.id SET cg.casino_id_uuid = c.uuid');
		DB::statement('UPDATE casino_provider cp JOIN casinos c ON cp.casino_id = c.id SET cp.casino_id_uuid = c.uuid');

		Schema::table('casino_game', function (Blueprint $table) {
			$table->dropColumn('casino_id');
		});

		Schema::table('casino_provider', function (Blueprint $table) {
			$table->dropColumn('casino_id');
		});

		DB::statement('ALTER TABLE casino_game CHANGE casino_id_uuid casino_id CHAR(36) NOT NULL');
		DB::statement('ALTER TABLE casino_provider CHANGE casino_id_uuid casino_id CHAR(36) NOT NULL');

		Schema::table('casino_game', function (Blueprint $table) {
			$table->primary(['casino_id', 'game_id']);
			$table->foreign('casino_id')->references('id')->on('casinos')->cascadeOnDelete();
		});

		Schema::table('casino_provider', function (Blueprint $table) {
			$table->primary(['casino_id', 'provider_id']);
			$table->foreign('casino_id')->references('id')->on('casinos')->cascadeOnDelete();
		});

		DB::statement('ALTER TABLE casinos DROP PRIMARY KEY');
		DB::statement('ALTER TABLE casinos DROP INDEX casinos_uuid_unique');
		DB::statement('ALTER TABLE casinos CHANGE id id_int BIGINT UNSIGNED NOT NULL');
		DB::statement('ALTER TABLE casinos CHANGE uuid id CHAR(36) NOT NULL');
		DB::statement('ALTER TABLE casinos ADD PRIMARY KEY (id)');

		Schema::table('casinos', function (Blueprint $table) {
			$table->dropColumn(['id_int', 'username']);
		});
	}
};
