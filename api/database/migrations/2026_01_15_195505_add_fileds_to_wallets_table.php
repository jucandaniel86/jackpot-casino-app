<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		public function up(): void
		{
			Schema::table('wallets', function (Blueprint $table) {
				$table->string('currency_id', 32)->nullable()->after('currency');   // ex SOLANA:PEP
				$table->string('currency_code', 16)->nullable()->after('currency_id'); // ex PEP
				$table->string('network', 16)->nullable()->after('currency_code');  // ex SOLANA

				$table->index(['holder_type', 'holder_id', 'currency_id'], 'wallets_holder_currency_id_idx');
				$table->index(['currency_id'], 'wallets_currency_id_idx');
			});
		}

		public function down(): void
		{
			Schema::table('wallets', function (Blueprint $table) {
				$table->dropIndex('wallets_holder_currency_id_idx');
				$table->dropIndex('wallets_currency_id_idx');
				$table->dropColumn(['currency_id', 'currency_code', 'network']);
			});
		}
	};