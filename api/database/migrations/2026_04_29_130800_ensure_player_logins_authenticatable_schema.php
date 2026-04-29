<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		public function up(): void
		{
			if (!Schema::hasTable('player_logins')) {
				Schema::create('player_logins', function (Blueprint $table) {
					$table->id();
					$table->timestamps();
					$table->string('authenticatable_type')->default('');
					$table->unsignedBigInteger('authenticatable_id');
					$table->string('user_agent')->default('');
					$table->string('ip')->default('');
					$table->longText('ip_data')->nullable();
					$table->string('device_type')->nullable();
					$table->string('device')->nullable();
					$table->string('platform')->nullable();
					$table->string('browser')->nullable();
					$table->string('city')->nullable();
					$table->string('region')->nullable();
					$table->string('country')->nullable();
					$table->string('session_id')->nullable();
					$table->string('remember_token')->nullable();
					$table->string('oauth_access_token_id')->nullable();
					$table->unsignedBigInteger('personal_access_token_id')->nullable();
					$table->timestamp('expires_at')->default(DB::raw("'0000-00-00 00:00:00'"));

					$table->index(['authenticatable_type', 'authenticatable_id', 'created_at'], 'player_logins_auth_latest_idx');
				});

				return;
			}

			Schema::table('player_logins', function (Blueprint $table) {
				if (!Schema::hasColumn('player_logins', 'created_at')) {
					$table->timestamp('created_at')->nullable();
				}
				if (!Schema::hasColumn('player_logins', 'updated_at')) {
					$table->timestamp('updated_at')->nullable();
				}
				if (!Schema::hasColumn('player_logins', 'authenticatable_type')) {
					$table->string('authenticatable_type')->default('');
				}
				if (!Schema::hasColumn('player_logins', 'authenticatable_id')) {
					$table->unsignedBigInteger('authenticatable_id')->default(0);
				}
				if (!Schema::hasColumn('player_logins', 'user_agent')) {
					$table->string('user_agent')->default('');
				}
				if (!Schema::hasColumn('player_logins', 'ip')) {
					$table->string('ip')->default('');
				}
				if (!Schema::hasColumn('player_logins', 'ip_data')) {
					$table->longText('ip_data')->nullable();
				}
				if (!Schema::hasColumn('player_logins', 'device_type')) {
					$table->string('device_type')->nullable();
				}
				if (!Schema::hasColumn('player_logins', 'device')) {
					$table->string('device')->nullable();
				}
				if (!Schema::hasColumn('player_logins', 'platform')) {
					$table->string('platform')->nullable();
				}
				if (!Schema::hasColumn('player_logins', 'browser')) {
					$table->string('browser')->nullable();
				}
				if (!Schema::hasColumn('player_logins', 'city')) {
					$table->string('city')->nullable();
				}
				if (!Schema::hasColumn('player_logins', 'region')) {
					$table->string('region')->nullable();
				}
				if (!Schema::hasColumn('player_logins', 'country')) {
					$table->string('country')->nullable();
				}
				if (!Schema::hasColumn('player_logins', 'session_id')) {
					$table->string('session_id')->nullable();
				}
				if (!Schema::hasColumn('player_logins', 'remember_token')) {
					$table->string('remember_token')->nullable();
				}
				if (!Schema::hasColumn('player_logins', 'oauth_access_token_id')) {
					$table->string('oauth_access_token_id')->nullable();
				}
				if (!Schema::hasColumn('player_logins', 'personal_access_token_id')) {
					$table->unsignedBigInteger('personal_access_token_id')->nullable();
				}
				if (!Schema::hasColumn('player_logins', 'expires_at')) {
					$table->timestamp('expires_at')->default(DB::raw("'0000-00-00 00:00:00'"));
				}
			});

			try {
				Schema::table('player_logins', function (Blueprint $table) {
					$table->index(['authenticatable_type', 'authenticatable_id', 'created_at'], 'player_logins_auth_latest_idx');
				});
			} catch (\Throwable) {
				// The index may already exist in environments where the table was created manually.
			}
		}

		public function down(): void
		{
			if (!Schema::hasTable('player_logins')) {
				return;
			}

			try {
				Schema::table('player_logins', function (Blueprint $table) {
					$table->dropIndex('player_logins_auth_latest_idx');
				});
			} catch (\Throwable) {
				// Keep rollback tolerant if the index was not created by this migration.
			}
		}
	};
