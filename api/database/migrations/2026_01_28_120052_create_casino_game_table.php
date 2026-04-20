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
        Schema::create('casino_game', function (Blueprint $table) {
            $table->uuid('casino_id');
            $table->unsignedBigInteger('game_id');
            $table->timestamps();

            $table->primary(['casino_id', 'game_id']);

            $table->foreign('casino_id')
                ->references('id')
                ->on('casinos')
                ->cascadeOnDelete();

            $table->foreign('game_id')
                ->references('id')
                ->on('games')
                ->cascadeOnDelete();

            $table->index(['game_id', 'casino_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('casino_game');
    }
};
