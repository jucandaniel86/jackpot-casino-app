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
			Schema::create('sliders', function (Blueprint $table) {
				$table->id();
				$table->string('name');
				$table->string('banner');
				$table->integer('page_id')->nullable()->default(0);
				$table->string('overlay')->nullable();
				$table->string('url')->nullable();
				$table->enum('action_type', ['OPEN_EXTRERNAL_PAGE', 'OPEN_INTERNAL_PAGE', 'OPEN_OVERLAY'])->default('OPEN_OVERLAY');
				$table->tinyInteger('is_same_tab')->nullable()->default(0);
				$table->tinyInteger('no_follow')->nullable()->default(0);
				$table->string('cta_label')->nullable();
				$table->timestamps();
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('sliders');
		}
	};