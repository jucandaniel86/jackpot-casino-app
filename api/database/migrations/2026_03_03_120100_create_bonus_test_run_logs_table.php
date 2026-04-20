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
        Schema::create('bonus_test_run_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('run_id')->index();
            $table->string('level', 20)->default('info')->index(); // info, warn, error, success
            $table->string('step_code', 80)->nullable()->index();
            $table->text('message');
            $table->json('context_json')->nullable();
            $table->timestamps();

            $table->foreign('run_id')
                ->references('id')
                ->on('bonus_test_runs')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonus_test_run_logs');
    }
};

