<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12/§19: inventory_adjustments — id (PK), variant_id (FK), delta, reason
// (restock/correction/return/damage), actor_id (FK users), created_at.
// Audit trail for every stock change.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('delta');
            $table->enum('reason', ['restock', 'correction', 'return', 'damage']);
            $table->foreignUuid('actor_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
