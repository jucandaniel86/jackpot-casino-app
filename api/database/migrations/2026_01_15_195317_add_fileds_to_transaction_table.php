<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		public function up(): void
		{
			Schema::table('transaction', function (Blueprint $table) {
				$table->string('currency_id', 32)->nullable()->after('currency');
				$table->string('currency_code', 16)->nullable()->after('currency_id');
				$table->string('network', 16)->nullable()->after('currency_code');

				$table->index(['currency_id'], 'transactions_currency_id_idx');
				$table->index(['wallet_id', 'currency_id'], 'transactions_wallet_currency_id_idx');
			});
		}

		public function down(): void
		{
			Schema::table('transaction', function (Blueprint $table) {
				$table->dropIndex('transactions_currency_id_idx');
				$table->dropIndex('transactions_wallet_currency_id_idx');
				$table->dropColumn(['currency_id', 'currency_code', 'network']);
			});
		}
	};