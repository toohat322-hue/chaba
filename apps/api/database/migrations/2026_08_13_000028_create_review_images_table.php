<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12: review_images — id (PK), review_id (FK), url.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_images', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('review_id')->constrained('reviews')->cascadeOnDelete();
            $table->string('url');

            $table->index('review_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_images');
    }
};
