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
        Schema::table('bonus_rules', function (Blueprint $table) {
            $table->string('campaign_type', 40)->nullable()->after('trigger_type');
            $table->decimal('deposit_bonus_multiplier', 12, 4)->nullable()->after('max_reward_amount');
            $table->unsignedInteger('real_wager_multiplier')->nullable()->after('wagering_multiplier');
            $table->unsignedInteger('bonus_wager_multiplier')->nullable()->after('real_wager_multiplier');
            $table->string('consume_priority', 20)->nullable()->after('bonus_wager_multiplier');
            $table->string('win_destination', 20)->nullable()->after('consume_priority');
            $table->decimal('max_convert_to_real_ui', 24, 8)->nullable()->after('win_destination');
            $table->unsignedInteger('expire_after_days')->nullable()->after('max_convert_to_real_ui');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bonus_rules', function (Blueprint $table) {
            $table->dropColumn([
                'campaign_type',
                'deposit_bonus_multiplier',
                'real_wager_multiplier',
                'bonus_wager_multiplier',
                'consume_priority',
                'win_destination',
                'max_convert_to_real_ui',
                'expire_after_days',
            ]);
        });
    }
};

