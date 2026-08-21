<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12: categories — id (PK), parent_id (FK categories, nullable, up to 3
// levels), name_ar, name_fr, name_en, slug (unique, indexed), image_url,
// sort_order, is_active.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            // Explicit primary() call (not the ->primary() column shortcut) so it's
            // queued before the self-referencing FK below — Postgres rejects a FK
            // to a PK that hasn't been added yet within the same migration, and
            // Laravel always defers the column-shortcut form to the end of the batch.
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'));
            $table->primary('id');
            $table->foreignUuid('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name_ar');
            $table->string('name_fr');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->string('image_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
