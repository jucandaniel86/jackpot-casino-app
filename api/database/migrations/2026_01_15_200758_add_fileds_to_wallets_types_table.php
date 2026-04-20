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
			Schema::table('wallet_types', function (Blueprint $table) {
				$table->string('currency_id', 32)->nullable()->after('code');   // ex SOLANA:PEP
				$table->string('currency_code', 16)->nullable()->after('currency_id'); // ex PEP
				$table->string('network', 16)->nullable()->after('currency_code');  // ex SOLANA
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::table('wallet_types', function (Blueprint $table) {
				//
			});
		}
	};