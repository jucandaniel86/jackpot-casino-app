<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wallet_types', function (Blueprint $table) {
            $table->string('purpose', 20)->default('real')->after('active');
            $table->index('purpose');
        });

        $now = now();
        $types = DB::table('wallet_types')->where('purpose', 'real')->get();

        foreach ($types as $type) {
            $exists = DB::table('wallet_types')
                ->where('purpose', 'bonus')
                ->where('currency_id', $type->currency_id)
                ->exists();

            if ($exists) {
                continue;
            }

            $networkData = is_array($type->network_data)
                ? $type->network_data
                : (json_decode((string)$type->network_data, true) ?: []);
            $networkData['bonus_internal'] = true;
            $networkData['bonus_auto_seeded'] = true;

            DB::table('wallet_types')->insert([
                'wallet_uuid' => (string)Str::uuid(),
                'code' => substr('B_' . (string)$type->code, 0, 10),
                'currency_id' => $type->currency_id,
                'currency_code' => $type->currency_code,
                'network' => $type->network,
                'name' => (string)$type->name . ' Bonus',
                'symbol' => $type->symbol,
                'icon' => $type->icon,
                'is_fiat' => 1, // internal bucket, no on-chain address
                'precision' => $type->precision,
                'supports_tag' => 0,
                'active' => $type->active,
                'purpose' => 'bonus',
                'min_amount' => 0,
                'network_data' => json_encode($networkData),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_types', function (Blueprint $table) {
            $table->dropIndex(['purpose']);
            $table->dropColumn('purpose');
        });
    }
};
