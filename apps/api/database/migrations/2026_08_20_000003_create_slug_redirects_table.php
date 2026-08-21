<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// SEO audit: renaming a product/category slug in admin 404s the old URL
// with no redirect, which breaks any external links/search rankings the old
// URL had. One small polymorphic-by-convention table (type + entity_id,
// rather than two near-identical product_slug_redirects/category_slug_
// redirects tables) records every retired slug so the storefront can 301
// old URLs to the current one. Populated from Admin\ProductController and
// Admin\CategoryController's update() actions when the slug changes.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slug_redirects', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'));
            $table->primary('id');
            $table->string('type');
            $table->string('old_slug');
            $table->uuid('entity_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['type', 'old_slug']);
            $table->index(['type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slug_redirects');
    }
};
