<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Admin-managed homepage hero carousel. Every slide belongs to a real
// product (cascadeOnDelete — a slide is meaningless without its product,
// so deleting the product removes the slide rather than leaving a dangling
// reference the public endpoint would need to defensively handle).
// title/subtitle/cta_label are nullable per-locale overrides — when empty
// the public controller falls back to the product's own name/description,
// so nothing here duplicates data the products table already owns.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_slides', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('image_url')->nullable();
            $table->string('mobile_image_url')->nullable();
            $table->string('title_ar')->nullable();
            $table->string('title_fr')->nullable();
            $table->string('title_en')->nullable();
            $table->string('subtitle_ar')->nullable();
            $table->string('subtitle_fr')->nullable();
            $table->string('subtitle_en')->nullable();
            $table->string('cta_label_ar')->nullable();
            $table->string('cta_label_fr')->nullable();
            $table->string('cta_label_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_slides');
    }
};
