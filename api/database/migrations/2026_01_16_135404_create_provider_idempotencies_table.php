<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		public function up(): void
		{
			Schema::create('provider_idempotencies', function (Blueprint $table) {
				$table->id();
				$table->string('provider', 50);
				$table->string('key', 191)->unique();      // idempotency key
				$table->string('endpoint', 50);            // bet|refund|balance
				$table->unsignedSmallInteger('http_status')->default(200);
				$table->json('response_json')->nullable(); // response body json
				$table->timestamps();
			});
		}

		public function down(): void
		{
			Schema::dropIfExists('provider_idempotencies');
		}
	};