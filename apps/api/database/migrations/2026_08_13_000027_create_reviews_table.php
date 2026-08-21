<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12/§7.12: reviews — id (PK), product_id (FK), user_id (FK), order_item_id
// (FK, nullable — links to verify purchase), rating (1-5), title, body,
// is_verified_purchase, status (pending/approved/rejected), created_at.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->boolean('is_verified_purchase')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('created_at')->useCurrent();

            $table->index('product_id');
        });

        DB::statement('ALTER TABLE reviews ADD CONSTRAINT reviews_rating_between_1_and_5 CHECK (rating >= 1 AND rating <= 5)');
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
