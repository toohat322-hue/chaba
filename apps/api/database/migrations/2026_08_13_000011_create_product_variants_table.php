<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12: product_variants — id (PK), product_id (FK), sku (unique, indexed),
// color, size, price_override (nullable), weight, created_at.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->unsignedBigInteger('price_override')->nullable();
            $table->decimal('weight', 8, 3)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('sku');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
