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
			Schema::create('tags_sections', function (Blueprint $table) {
				$table->unsignedBigInteger('tag_id');
				$table->uuid('section_id');
				$table->primary(['tag_id', 'section_id']);
				$table->integer('page_order')->index()->nullable()->default(0);

				$table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
				$table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('tags_sections');
		}
	};