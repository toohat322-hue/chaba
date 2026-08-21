<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12/§7.8: shipments — id (PK), order_id (FK), courier_partner,
// tracking_number, status (pending/picked_up/in_transit/out_for_delivery/
// delivered/failed/returned), estimated_delivery_date, delivered_at,
// failure_reason, created_at, updated_at. Index: order_id, tracking_number.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('order_id')->constrained('orders')->restrictOnDelete();
            $table->string('courier_partner')->nullable();
            $table->string('tracking_number')->nullable();
            $table->enum('status', [
                'pending', 'picked_up', 'in_transit', 'out_for_delivery',
                'delivered', 'failed', 'returned',
            ])->default('pending');
            $table->date('estimated_delivery_date')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
