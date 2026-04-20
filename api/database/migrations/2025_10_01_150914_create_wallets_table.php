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
			Schema::create('wallet_types', function (Blueprint $table) {
				$table->id();
				$table->uuid('wallet_uuid');
				$table->string('code', 10);
				$table->string('name', 50);
				$table->string('symbol', 50)->nullable();
				$table->string('icon', 50)->nullable();
				$table->tinyInteger('is_fiat')->default(0)->nullable();
				$table->tinyInteger('precision')->nullable();
				$table->tinyInteger('supports_tag')->nullable();
				$table->tinyInteger('active')->nullable();
				$table->decimal('min_amount', 19, 4)->nullable();
				$table->json('network_data')->nullable();
				$table->timestamps();
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('wallets');
		}
	};