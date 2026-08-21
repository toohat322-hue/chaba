<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the canonical 58-wilaya list from packages/shared/locations/wilayas.json
 * so the API and any future frontend static fallback read the same source of truth.
 */
class WilayaSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('../../packages/shared/locations/wilayas.json');
        $wilayas = json_decode(file_get_contents($path), true);

        foreach ($wilayas as $wilaya) {
            DB::table('wilayas')->updateOrInsert(
                ['code' => $wilaya['code']],
                [
                    'name_ar' => $wilaya['name_ar'],
                    'name_fr' => $wilaya['name_fr'],
                    'name_en' => $wilaya['name_en'],
                ],
            );
        }
    }
}
