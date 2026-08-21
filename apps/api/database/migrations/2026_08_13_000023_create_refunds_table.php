<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12/§7.9: refunds — id (PK), payment_id (FK), amount, reason, status,
// actor_id (FK users), created_at. §7.9 doesn't enumerate fixed refund status
// values (unlike payment/order status), so `status`/`reason` are plain strings
// rather than an invented enum, pending a Phase 6 (Payments) decision.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('reason')->nullable();
            $table->string('status');
            $table->foreignUuid('actor_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
