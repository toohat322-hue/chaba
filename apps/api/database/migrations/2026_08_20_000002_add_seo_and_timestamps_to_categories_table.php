<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// SEO audit: categories had no seo_title/seo_description (unlike Product,
// which already has both) and no timestamps at all ($timestamps = false,
// no timestamps() call in the original migration) — so a category page
// can't set a meta description and the sitemap can't set lastmod. Both are
// purely additive; existing rows get NULL seo fields (falls back to an
// auto-generated description) and updated_at/created_at backfilled to now.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function ($table) {
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function ($table) {
            $table->dropColumn(['seo_title', 'seo_description', 'created_at', 'updated_at']);
        });
    }
};
