<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('int_casino_id')->nullable();
            $table->string('title');
            $table->string('slug');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('type', 64);
            $table->json('rule')->nullable();
            $table->unsignedInteger('page_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->unique(['int_casino_id', 'slug'], 'rewards_casino_slug_unique');
            $table->index(['int_casino_id', 'type']);
            $table->index(['is_active', 'page_order']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
