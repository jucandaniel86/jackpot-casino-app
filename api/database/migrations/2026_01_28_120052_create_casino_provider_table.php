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
        Schema::create('casino_provider', function (Blueprint $table) {
            $table->uuid('casino_id');
            $table->unsignedBigInteger('provider_id');
            $table->timestamps();

            $table->primary(['casino_id', 'provider_id']);

            $table->foreign('casino_id')
                ->references('id')
                ->on('casinos')
                ->cascadeOnDelete();

            $table->foreign('provider_id')
                ->references('id')
                ->on('providers')
                ->cascadeOnDelete();

            $table->index(['provider_id', 'casino_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('casino_provider');
    }
};
