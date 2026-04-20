<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	use App\Enums\TransactionTypes;

	return new class extends Migration {
		/**
		 * Run the migrations.
		 */
		public function up(): void
		{
			Schema::create('bets', function (Blueprint $table) {
				$table->id();
				$table->uuid('transaction_id');
				$table->uuid('session_id');
				$table->integer('wallet_id');
				$table->integer('user_id');
				$table->string('operator_transaction_id')->nullable();
				$table->string('operator_round_id')->nullable();
				$table->string('currency', 3);
				$table->integer('ts');
				$table->integer('refund_ts');
				$table->decimal('balance_before', 64, 8)
					->default(0);
				$table->decimal('balance_after', 64, 8)
					->default(0);
				$table->decimal('stake', 64, 8)
					->default(0);
				$table->decimal('payout', 64, 8)
					->default(0);
				$table->decimal('refund', 64, 8)
					->default(0);
				$table->uuid('refund_transaction_id')->null();
				$table->enum('transaction_type', array_column(TransactionTypes::cases(), 'value'));
				$table->tinyInteger('round_finished')->default(0);
				$table->dateTime('when_placed');
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('bets');
		}
	};