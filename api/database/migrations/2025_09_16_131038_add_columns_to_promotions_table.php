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
			Schema::table('promotions', function (Blueprint $table) {
				$table->string('title');
				$table->string('subtitle')->nullable();
				$table->string('thumbnail')->nullable();
				$table->string('banner')->nullable();
				$table->text('description')->nullable();
				$table->text('smallDescription')->nullable();
				$table->text('howItWorks')->nullable();
				$table->text('terms')->nullable();
				$table->json('primaryAction')->nullable();
				$table->json('seo')->nullable();
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::table('promotions', function (Blueprint $table) {
				//
			});
		}
	};