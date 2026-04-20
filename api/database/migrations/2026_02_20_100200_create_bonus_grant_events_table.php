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
        Schema::create('bonus_grant_events', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('bonus_grant_id')->index();
            $table->string('event_type', 40)->index(); // issued, bet_debit, win_credit, expired, revoked, wagering_completed
            $table->decimal('amount_base', 65, 0)->default(0);

            $table->string('idempotency_key', 191)->unique();
            $table->string('reference_type', 32)->nullable()->index();
            $table->string('reference_id', 128)->nullable()->index();

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('bonus_grant_id')->references('id')->on('bonus_grants')->cascadeOnDelete();
            $table->index(['bonus_grant_id', 'event_type', 'created_at'], 'bonus_grant_events_grant_event_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonus_grant_events');
    }
};
