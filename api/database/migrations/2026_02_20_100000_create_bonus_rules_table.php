<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bonus_rules', function (Blueprint $table) {
            $table->id();

            $table->string('int_casino_id')->index();
            $table->string('name');
            $table->string('trigger_type', 40)->index(); // register, first_deposit, deposit_threshold, manual
            $table->json('condition_json')->nullable();

            $table->string('reward_type', 30); // fixed_amount | percentage
            $table->decimal('reward_value', 24, 8);
            $table->string('currency_id', 32)->nullable()->index();
            $table->string('currency_code', 16)->nullable();
            $table->decimal('max_reward_amount', 24, 8)->nullable();

            $table->unsignedInteger('wagering_multiplier')->default(0);
            $table->timestamp('valid_from')->nullable()->index();
            $table->timestamp('valid_until')->nullable()->index();

            $table->unsignedInteger('priority')->default(100)->index();
            $table->string('stacking_policy', 20)->default('stackable'); // stackable | exclusive
            $table->boolean('is_active')->default(false)->index();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->foreign('int_casino_id')
                ->references('int_casino_id')
                ->on('casinos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonus_rules');
    }
};
