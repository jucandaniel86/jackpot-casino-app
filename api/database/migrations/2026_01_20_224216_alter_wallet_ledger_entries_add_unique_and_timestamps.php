<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		public function up(): void
		{
			Schema::table('wallet_ledger_entries', function (Blueprint $table) {
				// 1) idempotency unique (dacă nu există deja)
				// Atenție: dacă ai duplicate deja, migrarea va pica -> vezi script cleanup mai jos.
				if (!$this->hasIndex('wallet_ledger_entries', 'wallet_ledger_entries_idempotency_key_unique')) {
					$table->string('idempotency_key')->unique()->change();
				}

				// 2) meta json (dacă e TEXT acum și vrei JSON)
				// Dacă e deja json, poți comenta change().
				// $table->json('meta')->nullable()->change();

				// 3) timestamps (dacă nu există)
				if (!Schema::hasColumn('wallet_ledger_entries', 'created_at')) {
					$table->timestamp('created_at')->useCurrent();
				}
				if (!Schema::hasColumn('wallet_ledger_entries', 'updated_at')) {
					$table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
				}
			});
		}

		public function down(): void
		{
			Schema::table('wallet_ledger_entries', function (Blueprint $table) {
				// optional rollback (nu e critic)
				// $table->dropUnique(['idempotency_key']);
			});
		}

		private function hasIndex(string $table, string $index): bool
		{
			// Laravel nu are helper direct fără doctrine, deci lăsăm simplu:
			// dacă nu ai doctrine/dbal instalat, scoate condition-ul și rulează manual.
			return false;
		}
	};