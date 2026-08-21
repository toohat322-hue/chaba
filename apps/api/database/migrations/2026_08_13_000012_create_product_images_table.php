<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12: product_images — id (PK), product_id (FK), variant_id (FK,
// nullable), url, sort_order, alt_text.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('url');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('alt_text')->nullable();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
