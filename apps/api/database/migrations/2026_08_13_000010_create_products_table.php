<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12: products — id (PK), category_id (FK), name_ar/fr/en, slug (unique,
// indexed), description_ar/fr/en, base_price, compare_at_price (nullable),
// status (draft/active/archived), seo_title, seo_description, avg_rating,
// review_count, created_at, updated_at. Index: slug, category_id, status.
// Money columns are integer DZD centimes (A6) — never floats.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('name_ar');
            $table->string('name_fr');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->text('description_ar')->nullable();
            $table->text('description_fr')->nullable();
            $table->text('description_en')->nullable();
            $table->unsignedBigInteger('base_price');
            $table->unsignedBigInteger('compare_at_price')->nullable();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->timestamps();

            $table->index('slug');
            $table->index('category_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
