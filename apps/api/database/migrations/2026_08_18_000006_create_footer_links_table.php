<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('footer_links', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('footer_column_id')->constrained('footer_columns')->cascadeOnDelete();
            $table->string('label_ar');
            $table->string('label_fr');
            $table->string('label_en');
            $table->string('url');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('footer_column_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('footer_links');
    }
};
