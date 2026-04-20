<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	use App\Enums\Zones;
	use App\Enums\ContainerSection;

	return new class extends Migration {
		/**
		 * Run the migrations.
		 */
		public function up(): void
		{
			Schema::create('sections', function (Blueprint $table) {
				$table->uuid('id')->primary();
				$table->enum('container', array_column(ContainerSection::cases(), 'value'))->default(ContainerSection::COLUMN->value);
				$table->uuid('parent_id')->nullable();
				$table->enum('zone', array_column(Zones::cases(), 'value'))->default(Zones::MAIN->value);
				$table->json('resolution_config')->nullable();
				$table->json('data')->nullable();
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('sections');
		}
	};