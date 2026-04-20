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
			Schema::create('wallets', function (Blueprint $table) {
				$table->bigIncrements('id');
				$table->morphs('holder');
				$table->string('name');
				$table->integer('wallet_type_id');
				$table->uuid('uuid')
					->unique();
				$table->json('meta')
					->nullable();
				$table->decimal('balance', 64, 8)
					->default(0);
				$table->unique(['holder_type', 'holder_id']);
				$table->string('currency');
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('wallets');
		}
	};