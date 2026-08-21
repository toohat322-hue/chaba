<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12: cart_items — id (PK), cart_id (FK), variant_id (FK), quantity,
// price_snapshot, created_at.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->foreignUuid('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('price_snapshot');
            $table->timestamp('created_at')->useCurrent();

            $table->index('cart_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
