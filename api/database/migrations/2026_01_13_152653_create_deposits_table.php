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
			Schema::create('deposits', function (Blueprint $table) {
				$table->bigIncrements('id');
				$table->unsignedBigInteger('wallet_id');
				$table->string('currency');              // ex: SOLANA:PEPAGY_SAN
				$table->string('txid', 128);             // Solana signature
				$table->string('to_address', 64)->nullable(); // token account / owner address
				$table->decimal('amount_base', 65, 0);   // exact base units
				$table->unsignedInteger('decimals');
				$table->unsignedBigInteger('block_time')->nullable();
				$table->enum('status', ['seen', 'confirmed', 'finalized'])->default('confirmed');
				$table->timestamps();

				$table->unique(['wallet_id', 'txid']);
				$table->index(['currency', 'block_time']);
				$table->foreign('wallet_id')->references('id')->on('wallets')->cascadeOnDelete();
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('deposits');
		}
	};