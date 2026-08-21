<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12: wishlists — id (PK), user_id (FK, nullable), session_token (guest),
// variant_id (FK), created_at. Unique: (user_id, variant_id).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlists', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('session_token')->nullable();
            $table->foreignUuid('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'variant_id']);
            $table->index('session_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
