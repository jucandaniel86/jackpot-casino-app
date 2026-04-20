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
			Schema::create('page_sections', function (Blueprint $table) {
				$table->unsignedBigInteger('page_id');
				$table->uuid('section_id');
				$table->primary(['page_id', 'section_id']);

				$table->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
				$table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('page_sections');
		}
	};