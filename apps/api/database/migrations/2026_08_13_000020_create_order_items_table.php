<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12: order_items — id (PK), order_id (FK), variant_id (FK),
// product_name_snapshot, sku_snapshot, unit_price, quantity, line_total.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignUuid('variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->string('product_name_snapshot');
            $table->string('sku_snapshot');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_total');

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
