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
			Schema::table('casinos', function (Blueprint $table) {
				$table->string('brand_id')->unique();
				$table->json('casino_api_urls')->nullable();
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::table('casinos', function (Blueprint $table) {
				$table->dropUnique(['brand_id']);
				$table->dropColumn(['brand_id', 'casino_api_urls']);
			});
		}
	};