<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds all ~1,541 communes from packages/shared/locations/communes.json —
 * sourced from othmanus/algeria-cities (public dataset matching the official
 * 58-wilaya division), not hand-typed. The earlier Phase 1 seed only covered
 * each wilaya's chef-lieu (58 rows) and was explicitly flagged as partial;
 * this replaces it with full coverage so the wilaya -> commune cascading
 * select in Checkout has real data for every wilaya, not just the capital.
 */
class CommuneSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('../../packages/shared/locations/communes.json');
        $communes = json_decode(file_get_contents($path), true);

        foreach ($communes as $commune) {
            DB::table('communes')->updateOrInsert(
                ['wilaya_code' => $commune['wilaya_code'], 'name_fr' => $commune['name_fr']],
                ['name_ar' => $commune['name_ar'], 'name_en' => $commune['name_en']],
            );
        }
    }
}
