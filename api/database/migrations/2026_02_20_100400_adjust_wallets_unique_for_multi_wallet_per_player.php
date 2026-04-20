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
        Schema::table('wallets', function (Blueprint $table) {
            // old: unique(holder_type, holder_id) blocked multiple wallets per player
            $table->dropUnique(['holder_type', 'holder_id']);
            $table->unique(['holder_type', 'holder_id', 'wallet_type_id'], 'wallets_holder_wallet_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropUnique('wallets_holder_wallet_type_unique');
            $table->unique(['holder_type', 'holder_id']);
        });
    }
};
