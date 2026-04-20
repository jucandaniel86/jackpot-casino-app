<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('tournaments', function (Blueprint $table) {
			$table->uuid('id')->primary();
			$table->string('name');
			$table->string('thumbnail')->nullable();
			$table->timestamp('started_at');
			$table->timestamp('ended_at');
			$table->string('status')->default('draft');
			$table->integer('point_rate')->default(10);
			$table->timestamps();
			$table->softDeletes();

			$table->index('status');
			$table->index('started_at');
			$table->index('ended_at');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('tournaments');
	}
};

