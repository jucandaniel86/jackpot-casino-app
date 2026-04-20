<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	use App\Enums\SectionStatus;

	return new class extends Migration {
		/**
		 * Run the migrations.
		 */
		public function up(): void
		{
			Schema::table('sections', function (Blueprint $table) {
				$table->enum('status', array_column(SectionStatus::cases(), 'value'))->default(SectionStatus::DRAFT->value);
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::table('sections', function (Blueprint $table) {
				//
			});
		}
	};