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
        Schema::create('manual_bonus_batches', function (Blueprint $table) {
            $table->id();

            $table->string('int_casino_id')->index();
            $table->string('name');
            $table->json('segment_filter_json')->nullable();
            $table->string('status', 20)->default('draft')->index(); // draft, processing, completed, failed
            $table->unsignedInteger('estimated_players')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->foreign('int_casino_id')
                ->references('int_casino_id')
                ->on('casinos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['int_casino_id', 'status', 'created_at'], 'manual_bonus_batches_casino_status_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_bonus_batches');
    }
};
