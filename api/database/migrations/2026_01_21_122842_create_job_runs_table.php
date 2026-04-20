<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		public function up(): void
		{
			Schema::create('job_runs', function (Blueprint $table) {
				$table->bigIncrements('id');
				$table->uuid('uuid')->unique();              // public id
				$table->string('job_class', 200);            // App\Jobs\...
				$table->string('job_name', 100)->nullable(); // "SolanaDepositScan"
				$table->string('queue', 50)->nullable();     // crypto/default
				$table->string('connection', 50)->nullable();// redis/database
				$table->enum('status', ['running', 'success', 'failed'])->index();
				$table->unsignedInteger('attempt')->default(1);

				$table->unsignedBigInteger('duration_ms')->nullable();
				$table->timestamp('started_at')->useCurrent();
				$table->timestamp('finished_at')->nullable();

				$table->json('context')->nullable();         // wallet_id, currency, txid etc
				$table->json('result')->nullable();          // deposits_found, sweep_txid...
				$table->text('error_message')->nullable();
				$table->longText('error_trace')->nullable();

				$table->timestamps();

				$table->index(['job_class', 'created_at']);
				$table->index(['job_name', 'created_at']);
			});
		}

		public function down(): void
		{
			Schema::dropIfExists('job_runs');
		}
	};