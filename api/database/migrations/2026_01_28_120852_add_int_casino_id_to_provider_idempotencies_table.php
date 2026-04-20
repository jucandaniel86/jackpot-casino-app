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
        Schema::table('provider_idempotencies', function (Blueprint $table) {
            $table->string('int_casino_id')->nullable()->index();
            $table->foreign('int_casino_id')
                ->references('int_casino_id')
                ->on('casinos')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_idempotencies', function (Blueprint $table) {
            $table->dropForeign(['int_casino_id']);
            $table->dropIndex(['int_casino_id']);
            $table->dropColumn('int_casino_id');
        });
    }
};
