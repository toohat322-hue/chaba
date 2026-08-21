<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12/§7.14: promotions — id (PK), name, scope (product/category/cart),
// target_id (nullable), discount_type, discount_value, start_date, end_date,
// is_active. target_id is intentionally not a DB-level FK: it polymorphically
// references products or categories depending on `scope` (or is null for a
// cart-wide promotion) — validated at the application layer, not the schema.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->string('name');
            $table->enum('scope', ['product', 'category', 'cart']);
            $table->uuid('target_id')->nullable();
            $table->string('discount_type');
            $table->unsignedBigInteger('discount_value');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->boolean('is_active')->default(true);

            $table->index(['scope', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
