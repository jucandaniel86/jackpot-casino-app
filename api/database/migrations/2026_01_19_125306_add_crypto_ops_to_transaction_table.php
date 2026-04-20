<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		public function up(): void
		{
			Schema::table('transaction', function (Blueprint $table) {
				if (!Schema::hasColumn('transaction', 'credited_at')) {
					$table->dateTime('credited_at')->nullable()->index();
				}
				if (!Schema::hasColumn('transaction', 'fee_base')) {
					$table->decimal('fee_base', 65, 0)->default(0);
				}
				if (!Schema::hasColumn('transaction', 'fee_currency')) {
					$table->string('fee_currency', 10)->default('SOL');
					$table->index(['fee_currency', 'created_at']);
				}
			});
		}

		public function down(): void
		{
			Schema::table('transaction', function (Blueprint $table) {
				if (Schema::hasColumn('transaction', 'credited_at')) {
					$table->dropIndex(['credited_at']);
					$table->dropColumn('credited_at');
				}
				if (Schema::hasColumn('transaction', 'fee_base')) {
					$table->dropColumn('fee_base');
				}
				if (Schema::hasColumn('transaction', 'fee_currency')) {
					// index numele poate diferi; la nevoie îl scoți manual
					$table->dropColumn('fee_currency');
				}
			});
		}
	};