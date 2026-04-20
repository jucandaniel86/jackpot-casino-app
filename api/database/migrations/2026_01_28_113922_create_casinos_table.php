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
			Schema::create('casinos', function (Blueprint $table) {
				$table->uuid('id')->primary();
				$table->string('int_casino_id')->unique();
				$table->string('name');
				$table->string('logo')->nullable();
				$table->text('casino_url')->nullable();
				$table->tinyInteger('active')->default(1)->index();
				$table->json('meta')->nullable();
				$table->timestamps();
			});
		}


		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('casinos');
		}
	};