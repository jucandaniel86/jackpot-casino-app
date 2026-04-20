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
        Schema::create('bonus_grants', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('bonus_rule_id')->nullable()->index();
            $table->string('int_casino_id')->index();
            $table->unsignedBigInteger('player_id')->index();
            $table->unsignedBigInteger('wallet_id_bonus')->index();

            $table->string('currency_id', 32)->nullable()->index();
            $table->string('currency_code', 16)->nullable();

            $table->decimal('amount_granted_base', 65, 0);
            $table->decimal('amount_remaining_base', 65, 0)->default(0)->index();

            $table->string('status', 20)->default('granted')->index(); // granted, active, consumed, expired, revoked
            $table->string('source_type', 30)->default('automatic')->index(); // automatic, manual_segment, manual_user
            $table->string('source_ref', 191)->nullable()->index();

            $table->timestamp('expires_at')->nullable()->index();

            $table->decimal('wagering_required_base', 65, 0)->default(0);
            $table->decimal('wagering_progress_base', 65, 0)->default(0);

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('bonus_rule_id')->references('id')->on('bonus_rules')->nullOnDelete();
            $table->foreign('int_casino_id')
                ->references('int_casino_id')
                ->on('casinos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreign('player_id')->references('id')->on('players')->cascadeOnDelete();
            $table->foreign('wallet_id_bonus')->references('id')->on('wallets')->cascadeOnDelete();

            $table->index(['player_id', 'status', 'expires_at'], 'bonus_grants_player_status_expires_idx');
            $table->index(['int_casino_id', 'status', 'created_at'], 'bonus_grants_casino_status_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonus_grants');
    }
};
