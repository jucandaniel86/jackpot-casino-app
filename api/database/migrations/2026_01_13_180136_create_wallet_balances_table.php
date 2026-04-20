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
			Schema::create('wallet_balances', function (Blueprint $table) {
				$table->unsignedBigInteger('wallet_id')->primary();
				$table->string('currency');
				$table->decimal('available_base', 65, 0)->default(0);
				$table->decimal('reserved_base', 65, 0)->default(0);
				$table->timestamps();

				$table->foreign('wallet_id')->references('id')->on('wallets')->cascadeOnDelete();
				$table->index(['currency']);
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('wallet_balances');
		}
	};