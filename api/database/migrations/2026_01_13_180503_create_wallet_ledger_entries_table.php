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
			Schema::create('wallet_ledger_entries', function (Blueprint $table) {
				$table->bigIncrements('id');
				$table->unsignedBigInteger('wallet_id');
				$table->string('currency');

				// deposit / withdraw / bet / win / rollback / sweep_in / sweep_out / adjustment
				$table->string('type', 32);

				// credit / debit
				$table->enum('direction', ['credit', 'debit']);

				// exact base units
				$table->decimal('amount_base', 65, 0);
				$table->unsignedInteger('decimals');

				// referințe: provider bet_id, round_id, solana signature etc.
				$table->string('reference_type', 32)->nullable();   // 'provider'|'solana'|'internal'
				$table->string('reference_id', 128)->nullable();    // bet_id / round_id / txid

				// idempotency (unic per wallet)
				$table->string('idempotency_key', 191);

				$table->json('meta')->nullable();
				$table->timestamps();

				$table->foreign('wallet_id')->references('id')->on('wallets')->cascadeOnDelete();
				$table->unique(['wallet_id', 'idempotency_key']);
				$table->index(['wallet_id', 'created_at']);
				$table->index(['currency', 'type']);
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('wallet_ledger_entries');
		}
	};