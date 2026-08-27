<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds one `home` delivery-fee row per wilaya at 0 DZD — deliberately not
 * invented courier pricing. Admin fills in real per-wilaya rates via the
 * Delivery Fees admin screen. No `pickup` rows: pickup/stop-desk isn't
 * offered yet (no courier partner location data exists).
 *
 * insertOrIgnore, not updateOrInsert: this runs on every deploy (part of
 * ProductionSeeder, via preDeployCommand), and updateOrInsert would reset
 * every wilaya's fee back to 0 on every single deploy, wiping out whatever
 * the admin had just configured. This only fills in wilayas that don't have
 * a row yet — an existing fee, whatever its value, is never touched again
 * once it exists.
 */
class DeliveryFeeSeeder extends Seeder
{
    public function run(): void
    {
        $wilayaCodes = DB::table('wilayas')->pluck('code');
        $now = now();

        DB::table('delivery_fees')->insertOrIgnore(
            $wilayaCodes->map(fn ($code) => [
                'wilaya_code' => $code,
                'delivery_method' => 'home',
                'fee' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(),
        );
    }
}
