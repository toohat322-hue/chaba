<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12/§7.10: order_status_history — id (PK), order_id (FK), status, note,
// actor_id (FK users, nullable for system), created_at.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_history', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->enum('status', [
                'pending', 'confirmed', 'processing', 'ready_to_ship', 'shipped',
                'out_for_delivery', 'delivered', 'cancelled', 'failed', 'returned',
                'refunded', 'partially_refunded',
            ]);
            $table->text('note')->nullable();
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
    }
};
