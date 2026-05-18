<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('wallet_id')->nullable();
            $table->string('int_casino_id')->nullable();
            $table->string('email');
            $table->string('reward_type', 64);
            $table->decimal('amount_base', 65, 0);
            $table->decimal('amount', 24, 8);
            $table->unsignedInteger('decimals');
            $table->string('currency_id', 32);
            $table->string('currency_code', 16);
            $table->string('status', 24)->default('claimed');
            $table->json('meta')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->foreign('player_id')->references('id')->on('players')->cascadeOnDelete();
            $table->foreign('wallet_id')->references('id')->on('wallets')->nullOnDelete();
            $table->unique(['player_id', 'reward_type'], 'reward_claims_player_reward_unique');
            $table->index(['email', 'reward_type']);
            $table->index(['int_casino_id', 'reward_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_claims');
    }
};
