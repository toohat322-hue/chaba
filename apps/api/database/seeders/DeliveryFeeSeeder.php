<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds one `home` delivery-fee row per wilaya at 0 DZD — deliberately not
 * invented courier pricing. Admin fills in real per-wilaya rates via the
 * Delivery Fees admin screen. No `pickup` rows: pickup/stop-desk isn't
 * offered yet (no courier partner location data exists).
 */
class DeliveryFeeSeeder extends Seeder
{
    public function run(): void
    {
        $wilayaCodes = DB::table('wilayas')->pluck('code');

        foreach ($wilayaCodes as $code) {
            DB::table('delivery_fees')->updateOrInsert(
                ['wilaya_code' => $code, 'delivery_method' => 'home'],
                ['fee' => 0, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }
}
