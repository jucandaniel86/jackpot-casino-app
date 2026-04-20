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
			Schema::create('casino_api_requests', function (Blueprint $table) {
				$table->increments('id');
				$table->string('brand_id');
				$table->string('api_path');
				$table->json('api_request');
				$table->json('api_response');
				$table->tinyInteger('status');
				$table->integer('ts');
				$table->string('provider');
				$table->enum('request_type', ['start', 'balance', 'betwin', 'refund']);
				$table->enum('server_request_type', ['POST', 'GET', 'PUT', 'OPTIONS']);
				$table->string('session');

				$table->index('session');
				$table->index('brand_id');
				$table->index('provider');
				$table->foreign('brand_id')
					->references('brand_id')
					->on('casinos');
				$table->foreign('provider')
					->references('internal_provider_id')
					->on('providers');
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('casino_api_requests');
		}
	};