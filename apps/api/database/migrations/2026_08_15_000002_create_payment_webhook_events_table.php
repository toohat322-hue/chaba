<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Payment webhook idempotency/audit log (PRD §7.9 webhook handling). Every
// inbound webhook is logged here before processing; a unique (gateway,
// payload_hash) pair means a byte-identical redelivery from the gateway is
// recognized as a duplicate and never reprocessed (PaymentWebhookController).
// payload_hash is a sha256 of the raw request body — a pragmatic idempotency
// key given no real CIB/Edahabia webhook docs exist yet to know whether they
// send a stable event ID; revisit once real payload shapes are known.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->string('gateway');
            $table->string('payload_hash', 64);
            $table->string('transaction_ref')->nullable();
            $table->jsonb('payload');
            $table->enum('status', ['received', 'processed', 'failed'])->default('received');
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['gateway', 'payload_hash']);
            $table->index('transaction_ref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
    }
};
