<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	use App\Enums\ContainerSection;

	return new class extends Migration {
		/**
		 * Run the migrations.
		 */
		public function up(): void
		{
			Schema::create('section_global_configs', function (Blueprint $table) {
				$table->id();
				$table->enum('container', array_column(ContainerSection::cases(), 'value'))->default(ContainerSection::COLUMN->value);
				$table->json('resolution_config');
				$table->json('data');
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('section_global_configs');
		}
	};