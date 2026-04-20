<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	use App\Enums\LinkActionTypes;

	return new class extends Migration {
		/**
		 * Run the migrations.
		 */
		public function up(): void
		{
			Schema::create('menus', function (Blueprint $table) {
				$table->id();
				$table->uuid('menu_id');
				$table->string('title');
				$table->string('icon')->nullable();
				$table->integer('item_order')->default(0);
				$table->enum('position', ['HEADER', 'FOOTER', 'SIDEBAR'])->default('SIDEBAR');
				$table->enum('action_type', array_column(LinkActionTypes::cases(), 'value'))->default('OPEN_INTERNAL_PAGE');
				$table->tinyInteger('is_same_tab')->nullable()->default(0);
				$table->integer('page_id')->nullable()->default(0);
				$table->integer('game_id')->nullable()->default(0);
				$table->integer('promotion_id')->nullable()->default(0);
				$table->string('overlay')->nullable();
				$table->text('external_link')->nullable();
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('menus');
		}
	};