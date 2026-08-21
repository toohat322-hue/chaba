<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Footer CMS: the small trust-badge row shown in the footer (currently
// hardcoded as "COD available" / "flexible returns"), made admin-editable.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('footer_features', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->string('icon');
            $table->string('title_ar');
            $table->string('title_fr');
            $table->string('title_en');
            $table->string('description_ar')->nullable();
            $table->string('description_fr')->nullable();
            $table->string('description_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('footer_features');
    }
};
