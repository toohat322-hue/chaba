<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12: product_views — id (PK), user_id (FK, nullable), session_token,
// product_id (FK), viewed_at. Feeds recently-viewed and analytics.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_views', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_token')->nullable();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamp('viewed_at')->useCurrent();

            $table->index(['user_id', 'viewed_at']);
            $table->index(['session_token', 'viewed_at']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_views');
    }
};
