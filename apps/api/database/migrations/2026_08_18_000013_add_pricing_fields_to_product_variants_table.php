<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adds structured size (size_value + size_unit, replacing the free-text
// `size` column going forward — that column is left in place, unused, not
// dropped: every existing variant has size=NULL today, confirmed via
// ProductSeeder, so there's no real data to migrate) plus per-variant
// compare_at_price/sort_order/is_active, mirroring product-level fields
// and the is_active/sort_order convention already used by the Footer CMS
// tables.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->unsignedBigInteger('compare_at_price')->nullable();
            $table->decimal('size_value', 8, 2)->nullable();
            $table->string('size_unit', 10)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->unique(['product_id', 'size_value', 'size_unit'], 'product_variants_product_size_unique');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique('product_variants_product_size_unique');
            $table->dropColumn(['compare_at_price', 'size_value', 'size_unit', 'sort_order', 'is_active']);
        });
    }
};
