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
			Schema::create('player_profiles', function (Blueprint $table) {
				$table->id();
				$table->integer('player_id');
				$table->string('first_name')->nullable();
				$table->string('last_name')->nullable();
				$table->date('birthday')->nullable();
				$table->string('country')->nullable();
				$table->string('postal_code')->nullable();
				$table->text('address')->nullable();
				$table->string('city')->nullable();
				$table->string('phone')->nullable();
				$table->string('language')->nullable()->default('en');
				$table->string('display_fiat_currency')->nullable()->default('EUR');
				$table->tinyInteger('marketing_emails')->nullable()->default(0);
				$table->tinyInteger('hide_username')->nullable()->default(1);
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('player_profiles');
		}
	};