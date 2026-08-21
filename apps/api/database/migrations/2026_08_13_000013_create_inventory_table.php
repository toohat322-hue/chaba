<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12/§20: inventory — id (PK), variant_id (FK, unique), stock_quantity,
// reserved_quantity, available_quantity (generated = stock - reserved),
// low_stock_threshold, updated_at. Index: variant_id.
// available_quantity is a Postgres STORED generated column so oversell checks
// (available_quantity > 0) are always consistent, never recomputed ad hoc.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('variant_id')->unique()->constrained('product_variants')->cascadeOnDelete();
            $table->integer('stock_quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('available_quantity')
                ->storedAs('stock_quantity - reserved_quantity');
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
