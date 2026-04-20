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
		Schema::create('player_login_verification_codes', function (Blueprint $table) {
			$table->id();
			$table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
			$table->string('login_token_hash', 64)->unique();
			$table->string('code_hash');
			$table->unsignedTinyInteger('attempts')->default(0);
			$table->unsignedTinyInteger('max_attempts')->default(5);
			$table->string('ip_address', 45)->nullable();
			$table->string('user_agent_hash', 64)->nullable();
			$table->timestamp('expires_at');
			$table->timestamp('consumed_at')->nullable();
			$table->timestamps();

			$table->index(['player_id', 'expires_at']);
			$table->index('expires_at');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('player_login_verification_codes');
	}
};
