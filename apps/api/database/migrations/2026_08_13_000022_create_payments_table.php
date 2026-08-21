<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12/§7.9: payments — id (PK), order_id (FK), provider (cod/cib/edahabia),
// transaction_ref (nullable), status, amount, currency (DZD), raw_response
// (jsonb), created_at, updated_at. Index: order_id, transaction_ref.
// `status` here is the per-transaction status from §7.9 ("Transaction status":
// initiated/processing/success/failed/cancelled/expired) — distinct from the
// order-level payment_status aggregate stored on `orders`.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('order_id')->constrained('orders')->restrictOnDelete();
            $table->enum('provider', ['cod', 'cib', 'edahabia']);
            $table->string('transaction_ref')->nullable();
            $table->enum('status', ['initiated', 'processing', 'success', 'failed', 'cancelled', 'expired'])
                ->default('initiated');
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('DZD');
            $table->jsonb('raw_response')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('transaction_ref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
