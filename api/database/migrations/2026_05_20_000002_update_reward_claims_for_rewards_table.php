<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reward_claims', function (Blueprint $table) {
            $table->index('player_id', 'reward_claims_player_id_index');
        });

        Schema::table('reward_claims', function (Blueprint $table) {
            $table->dropUnique('reward_claims_player_reward_unique');
            $table->dropIndex(['email', 'reward_type']);
            $table->dropIndex(['int_casino_id', 'reward_type']);
        });

        Schema::table('reward_claims', function (Blueprint $table) {
            $table->foreignId('reward_id')
                ->nullable()
                ->after('int_casino_id')
                ->constrained('rewards')
                ->nullOnDelete();
            $table->string('period_key', 64)
                ->default('lifetime')
                ->after('reward_id');
            $table->dropColumn('reward_type');

            $table->unique(
                ['player_id', 'reward_id', 'period_key'],
                'reward_claims_player_reward_period_unique'
            );
            $table->index(['reward_id', 'period_key']);
            $table->index(['email', 'reward_id']);
            $table->index(['int_casino_id', 'reward_id']);
        });
    }

    public function down(): void
    {
        Schema::table('reward_claims', function (Blueprint $table) {
            $table->dropUnique('reward_claims_player_reward_period_unique');
            $table->dropIndex(['reward_id', 'period_key']);
            $table->dropIndex(['email', 'reward_id']);
            $table->dropIndex(['int_casino_id', 'reward_id']);
            $table->dropForeign(['reward_id']);
        });

        Schema::table('reward_claims', function (Blueprint $table) {
            $table->string('reward_type', 64)
                ->nullable()
                ->after('email');
            $table->dropColumn(['reward_id', 'period_key']);

            $table->unique(['player_id', 'reward_type'], 'reward_claims_player_reward_unique');
            $table->index(['email', 'reward_type']);
            $table->index(['int_casino_id', 'reward_type']);
            $table->dropIndex('reward_claims_player_id_index');
        });
    }
};
