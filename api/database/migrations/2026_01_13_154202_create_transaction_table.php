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
			Schema::create('transaction', function (Blueprint $table) {
				$table->bigIncrements('id');
				$table->unsignedBigInteger('wallet_id');
				$table->uuid('uuid')->unique();                 // id public
				$table->string('currency');                     // same as wallet.currency
				$table->enum('type', ['deposit', 'withdraw']);
				$table->enum('status', ['pending', 'confirmed', 'failed'])->default('confirmed');

				// sume exacte
				$table->decimal('amount_base', 65, 0);          // exact, base units
				$table->unsignedInteger('decimals');

				// pentru UI (opțional)
				$table->decimal('amount', 64, 8)->nullable();   // truncated/rounded UI

				// referințe on-chain
				$table->string('txid', 128)->nullable();        // signature / hash
				$table->string('from_address', 128)->nullable();
				$table->string('to_address', 128)->nullable();

				// metadata
				$table->json('meta')->nullable();
				$table->unsignedBigInteger('block_time')->nullable();
				$table->timestamps();

				$table->index(['wallet_id', 'created_at']);
				$table->index(['currency', 'type', 'status']);
				$table->unique(['wallet_id', 'type', 'txid']); // previne duplicate dacă txid există
				$table->foreign('wallet_id')->references('id')->on('wallets')->cascadeOnDelete();
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('transaction');
		}
	};