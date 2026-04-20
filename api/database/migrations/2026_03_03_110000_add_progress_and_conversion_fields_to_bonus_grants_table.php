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
        Schema::table('bonus_grants', function (Blueprint $table) {
            $table->decimal('real_wager_required_base', 65, 0)->default(0)->after('wagering_progress_base');
            $table->decimal('real_wager_progress_base', 65, 0)->default(0)->after('real_wager_required_base');
            $table->decimal('max_convert_to_real_base', 65, 0)->default(0)->after('real_wager_progress_base');
            $table->decimal('converted_to_real_base', 65, 0)->default(0)->after('max_convert_to_real_base');
            $table->boolean('withdraw_lock')->default(false)->index()->after('converted_to_real_base');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bonus_grants', function (Blueprint $table) {
            $table->dropColumn([
                'real_wager_required_base',
                'real_wager_progress_base',
                'max_convert_to_real_base',
                'converted_to_real_base',
                'withdraw_lock',
            ]);
        });
    }
};

