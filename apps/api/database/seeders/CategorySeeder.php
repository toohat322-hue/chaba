<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Matches the four nav items already live in the homepage header
// (apps/web/messages/*.json Nav.*) — seeding these makes those links
// resolvable once category pages exist, instead of pointing at nothing.
class CategorySeeder extends Seeder
{
    private const CATEGORIES = [
        ['slug' => 'saudi-perfumes', 'name_ar' => 'عطور سعودية', 'name_fr' => 'Parfums saoudiens', 'name_en' => 'Saudi Perfumes', 'sort_order' => 1],
        ['slug' => 'turkish-perfumes', 'name_ar' => 'عطور تركية', 'name_fr' => 'Parfums turcs', 'name_en' => 'Turkish Perfumes', 'sort_order' => 2],
        ['slug' => 'oud-oil', 'name_ar' => 'دهن العود', 'name_fr' => "Huile d'oud", 'name_en' => 'Oud Oil', 'sort_order' => 3],
        ['slug' => 'exclusive-offers', 'name_ar' => 'عروض حصرية', 'name_fr' => 'Offres exclusives', 'name_en' => 'Exclusive Offers', 'sort_order' => 4],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $category['slug']],
                [
                    'name_ar' => $category['name_ar'],
                    'name_fr' => $category['name_fr'],
                    'name_en' => $category['name_en'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
