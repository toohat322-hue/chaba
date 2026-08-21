<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Real catalog rows replacing what was previously hardcoded in
 * apps/web/lib/demo-data.ts (same names/prices — now the actual source of
 * truth is Postgres, per the "no mock data" requirement). No real product
 * photography exists yet (flagged since Phase 1); products are seeded
 * without images and the frontend's placeholder art covers the gap until
 * real photos are uploaded through admin (a later phase).
 */
class ProductSeeder extends Seeder
{
    private const PRODUCTS = [
        [
            'sku' => 'CHB-OUD-MALAKI-001',
            'category' => 'oud-oil',
            'name_ar' => 'عود الملكي', 'name_fr' => 'Oud Al Malaki', 'name_en' => 'Oud Al Malaki',
            'description_ar' => 'دهن عود كمبودي فاخر بعمق دخاني أصيل، مستخلص بعناية للحفاظ على نقائه.',
            'description_fr' => 'Huile d\'oud cambodgien raffinée, à la profondeur boisée authentique, extraite avec soin pour préserver sa pureté.',
            'description_en' => 'Refined Cambodian oud oil with an authentic smoky depth, carefully extracted to preserve its purity.',
            'base_price' => 850000, 'compare_at_price' => null, 'featured' => true,
        ],
        [
            'sku' => 'CHB-WARD-TAIFI-001',
            'category' => 'turkish-perfumes',
            'name_ar' => 'ورد طائفي', 'name_fr' => 'Ward Taifi', 'name_en' => 'Ward Taifi',
            'description_ar' => 'عطر ورد طائفي فاخر، نفحة زهرية دافئة تدوم طويلًا.',
            'description_fr' => 'Parfum floral de rose de Taëf, une note chaude et florale à la tenue longue durée.',
            'description_en' => 'Luxurious Taif rose fragrance — a warm floral note with impressive longevity.',
            'base_price' => 620000, 'compare_at_price' => 750000, 'featured' => true,
        ],
        [
            'sku' => 'CHB-MISK-DAHABI-001',
            'category' => 'saudi-perfumes',
            'name_ar' => 'مسك ذهبي', 'name_fr' => 'Misk Dahabi', 'name_en' => 'Misk Dahabi',
            'description_ar' => 'مسك ذهبي ناعم بلمسة دافئة، مثالي للاستخدام اليومي.',
            'description_fr' => 'Musc doré doux à la touche chaleureuse, idéal pour un usage quotidien.',
            'description_en' => 'Soft golden musk with a warm touch, perfect for everyday wear.',
            'base_price' => 450000, 'compare_at_price' => null, 'featured' => true,
        ],
        [
            'sku' => 'CHB-ISTANBUL-NIGHTS-001',
            'category' => 'turkish-perfumes',
            'name_ar' => 'ليالي إسطنبول', 'name_fr' => 'Nuits d\'Istanbul', 'name_en' => 'Istanbul Nights',
            'description_ar' => 'مزيج شرقي معاصر مستوحى من ليالي إسطنبول الساحرة.',
            'description_fr' => 'Un mélange oriental contemporain inspiré des nuits envoûtantes d\'Istanbul.',
            'description_en' => 'A contemporary oriental blend inspired by Istanbul\'s enchanting nights.',
            'base_price' => 580000, 'compare_at_price' => null, 'featured' => true,
        ],
        [
            'sku' => 'CHB-AMBER-SULTAN-001',
            'category' => 'saudi-perfumes',
            'name_ar' => 'عنبر السلطان', 'name_fr' => 'Ambre Sultan', 'name_en' => 'Amber Sultan',
            'description_ar' => 'عنبر فاخر بحضور قوي ومهيب، لعشاق العطور الشرقية الغنية.',
            'description_fr' => 'Ambre luxueux à la présence forte et majestueuse, pour les amateurs de parfums orientaux riches.',
            'description_en' => 'A luxurious amber with a bold, majestic presence, for lovers of rich oriental fragrances.',
            'base_price' => 720000, 'compare_at_price' => null, 'featured' => true,
        ],
        [
            'sku' => 'CHB-DAHN-OUD-CAMBODI-001',
            'category' => 'oud-oil',
            'name_ar' => 'دهن العود الكمبودي', 'name_fr' => 'Dahn Al Oud Cambodgien', 'name_en' => 'Cambodian Dahn Al Oud',
            'description_ar' => 'أجود أنواع دهن العود الكمبودي الأصيل، تركيز عالٍ ورائحة تدوم طوال اليوم.',
            'description_fr' => 'Le meilleur dahn al oud cambodgien authentique, haute concentration et tenue toute la journée.',
            'description_en' => 'The finest authentic Cambodian dahn al oud — high concentration with all-day longevity.',
            'base_price' => 1250000, 'compare_at_price' => null, 'featured' => true,
        ],
        [
            'sku' => 'CHB-BOSPHORUS-ROSE-001',
            'category' => 'turkish-perfumes',
            'name_ar' => 'وردة البوسفور', 'name_fr' => 'Rose du Bosphore', 'name_en' => 'Bosphorus Rose',
            'description_ar' => 'عطر وردي أنيق مستوحى من ضفاف البوسفور، توازن رقيق بين النعومة والحيوية.',
            'description_fr' => 'Un parfum de rose élégant inspiré des rives du Bosphore, un équilibre délicat entre douceur et vivacité.',
            'description_en' => 'An elegant rose fragrance inspired by the Bosphorus shores, delicately balancing softness and vibrancy.',
            'base_price' => 490000, 'compare_at_price' => null, 'featured' => true,
        ],
        [
            'sku' => 'CHB-HIJAZ-LEGACY-001',
            'category' => 'saudi-perfumes',
            'name_ar' => 'إرث الحجاز', 'name_fr' => 'Héritage du Hijaz', 'name_en' => 'Hijaz Legacy',
            'description_ar' => 'عطر يحمل عبق التراث الحجازي الأصيل بلمسة عصرية راقية.',
            'description_fr' => 'Un parfum qui porte l\'essence authentique du patrimoine du Hijaz, avec une touche moderne et raffinée.',
            'description_en' => 'A fragrance carrying the authentic essence of Hijazi heritage with a refined modern touch.',
            'base_price' => 690000, 'compare_at_price' => 820000, 'featured' => true,
        ],
    ];

    public function run(): void
    {
        $categoryIds = Category::pluck('id', 'slug');

        foreach (self::PRODUCTS as $row) {
            $slug = Str::slug($row['name_en']);

            $product = Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $categoryIds[$row['category']] ?? null,
                    'name_ar' => $row['name_ar'],
                    'name_fr' => $row['name_fr'],
                    'name_en' => $row['name_en'],
                    'description_ar' => $row['description_ar'],
                    'description_fr' => $row['description_fr'],
                    'description_en' => $row['description_en'],
                    'base_price' => $row['base_price'],
                    'compare_at_price' => $row['compare_at_price'],
                    'status' => 'active',
                    'featured' => $row['featured'],
                ],
            );

            $variant = ProductVariant::firstOrCreate(
                ['sku' => $row['sku']],
                ['product_id' => $product->id],
            );

            Inventory::updateOrCreate(
                ['variant_id' => $variant->id],
                ['stock_quantity' => 50, 'reserved_quantity' => 0, 'low_stock_threshold' => 5],
            );
        }

        $this->seedMultiSizeDemoProduct($categoryIds);
    }

    /**
     * A real, visibly-testable example of size-based variant pricing (every
     * other seeded product above is single-variant) — one size discounted,
     * one intentionally out of stock, so the storefront's disabled-card and
     * compare-at-price states are exercisable without manually creating
     * data through the admin first.
     */
    private function seedMultiSizeDemoProduct($categoryIds): void
    {
        $product = Product::updateOrCreate(
            ['slug' => 'chaba-signature'],
            [
                'category_id' => $categoryIds['saudi-perfumes'] ?? null,
                'name_ar' => 'شابة التوقيعي', 'name_fr' => 'CHABA Signature', 'name_en' => 'CHABA Signature',
                'description_ar' => 'التوقيع العطري لشابة — تركيبة متوازنة متوفرة بعدة أحجام لتناسب كل استخدام.',
                'description_fr' => 'La signature olfactive de CHABA — une composition équilibrée disponible en plusieurs tailles.',
                'description_en' => 'CHABA\'s signature blend — a balanced composition available in multiple sizes.',
                'base_price' => 350000,
                'compare_at_price' => null,
                'status' => 'active',
                'featured' => false,
            ],
        );

        $sizes = [
            ['sku' => 'CHB-SIGNATURE-10ML', 'size_value' => 10, 'price' => 350000, 'compare' => null, 'stock' => 20, 'sort' => 0],
            ['sku' => 'CHB-SIGNATURE-20ML', 'size_value' => 20, 'price' => 600000, 'compare' => null, 'stock' => 15, 'sort' => 1],
            ['sku' => 'CHB-SIGNATURE-50ML', 'size_value' => 50, 'price' => 1200000, 'compare' => 1400000, 'stock' => 8, 'sort' => 2],
            ['sku' => 'CHB-SIGNATURE-100ML', 'size_value' => 100, 'price' => 2000000, 'compare' => null, 'stock' => 0, 'sort' => 3],
        ];

        foreach ($sizes as $size) {
            $variant = ProductVariant::updateOrCreate(
                ['sku' => $size['sku']],
                [
                    'product_id' => $product->id,
                    'size_value' => $size['size_value'],
                    'size_unit' => 'ml',
                    'price_override' => $size['price'],
                    'compare_at_price' => $size['compare'],
                    'sort_order' => $size['sort'],
                    'is_active' => true,
                ],
            );

            Inventory::updateOrCreate(
                ['variant_id' => $variant->id],
                ['stock_quantity' => $size['stock'], 'reserved_quantity' => 0, 'low_stock_threshold' => 5],
            );
        }
    }
}
