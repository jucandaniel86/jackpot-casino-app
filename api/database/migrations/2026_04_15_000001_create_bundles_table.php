<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('bundles', function (Blueprint $table) {
			$table->uuid('id')->primary();

			$table->string('name');
			$table->string('slug')->unique();
			$table->string('short_description', 500)->nullable();
			$table->text('description')->nullable();

			$table->decimal('price_amount', 18, 2)->default(0);
			$table->string('price_currency', 10)->default('EUR');
			$table->decimal('gc_amount', 18, 2)->default(0);
			$table->decimal('coin_amount', 18, 2)->default(0);

			$table->string('thumbnail', 500)->nullable();
			$table->string('icon', 500)->nullable();
			$table->string('label')->nullable();
			$table->string('subtitle')->nullable();
			$table->string('cta_text', 100)->nullable();
			$table->string('badge_text', 100)->nullable();
			$table->string('badge_color', 50)->nullable();
			$table->string('border_color', 50)->nullable();
			$table->string('accent_color', 50)->nullable();
			$table->string('background_color', 50)->nullable();
			$table->string('text_color', 50)->nullable();
			$table->string('ribbon_text', 100)->nullable();
			$table->string('tag', 100)->nullable();
			$table->string('tag_color', 50)->nullable();
			$table->string('image_url', 500)->nullable();

			$table->boolean('is_active')->default(true)->index();
			$table->boolean('is_featured')->default(false)->index();
			$table->boolean('is_popular')->default(false)->index();
			$table->integer('sort_order')->default(0)->index();

			$table->json('metadata')->nullable();
			$table->timestamp('starts_at')->nullable()->index();
			$table->timestamp('ends_at')->nullable()->index();

			$table->timestamps();
			$table->softDeletes();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('bundles');
	}
};
