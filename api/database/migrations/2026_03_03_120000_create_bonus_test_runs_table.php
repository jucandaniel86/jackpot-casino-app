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
        Schema::create('bonus_test_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('int_casino_id')->nullable()->index();
            $table->string('scenario', 80)->index();
            $table->string('status', 20)->default('pending')->index(); // pending, running, passed, failed
            $table->unsignedBigInteger('requested_by')->nullable()->index();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('finished_at')->nullable()->index();
            $table->json('summary_json')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('int_casino_id')
                ->references('int_casino_id')
                ->on('casinos')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonus_test_runs');
    }
};

