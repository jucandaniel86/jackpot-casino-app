<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		public function up(): void
		{
			Schema::create('withdraw_requests', function (Blueprint $table) {
				$table->bigIncrements('id');
				$table->uuid('uuid')->unique();

				$table->unsignedBigInteger('wallet_id');
				$table->unsignedBigInteger('player_id');

				$table->string('currency');            // SOLANA:PEP
				$table->decimal('amount_base', 65, 0); // exact base units
				$table->unsignedInteger('decimals');

				$table->decimal('amount_ui', 64, 8)->nullable();
				$table->string('to_address', 128);

				$table->enum('status', ['pending', 'approved', 'completed', 'rejected', 'failed'])->default('pending');

				$table->string('admin_note')->nullable();
				$table->string('reject_reason')->nullable();

				$table->string('txid', 128)->nullable();
				$table->unsignedBigInteger('completed_at')->nullable();

				$table->json('meta')->nullable();
				$table->timestamps();

				$table->index(['status', 'created_at']);
				$table->index(['player_id', 'created_at']);
				$table->index(['wallet_id', 'created_at']);

				$table->foreign('wallet_id')->references('id')->on('wallets')->cascadeOnDelete();
			});
		}

		public function down(): void
		{
			Schema::dropIfExists('withdraw_requests');
		}
	};