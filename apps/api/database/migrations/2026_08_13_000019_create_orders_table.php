<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12/§7.9/§7.10: orders — id (PK), order_number (unique, indexed), user_id
// (FK, nullable for guest), guest_name/phone/email, address_id (FK, snapshot),
// delivery_method (home/pickup), payment_method (cod/cib/edahabia),
// payment_status, order_status, subtotal, discount_total, delivery_fee,
// tax_total, grand_total, coupon_id (FK, nullable), notes, created_at,
// updated_at. Index: order_number, user_id, order_status, created_at.
// order_status/payment_status enums match the states enumerated in §7.9/§7.10.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->string('order_number')->unique();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('guest_name')->nullable();
            $table->string('guest_phone')->nullable();
            $table->string('guest_email')->nullable();
            $table->foreignUuid('address_id')->constrained('addresses')->restrictOnDelete();
            $table->enum('delivery_method', ['home', 'pickup']);
            $table->enum('payment_method', ['cod', 'cib', 'edahabia']);
            $table->enum('payment_status', [
                'pending', 'authorized', 'captured', 'failed', 'refunded',
                'partially_refunded', 'cod_pending_collection', 'cod_collected',
            ])->default('pending');
            $table->enum('order_status', [
                'pending', 'confirmed', 'processing', 'ready_to_ship', 'shipped',
                'out_for_delivery', 'delivered', 'cancelled', 'failed', 'returned',
                'refunded', 'partially_refunded',
            ])->default('pending');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount_total')->default(0);
            $table->unsignedBigInteger('delivery_fee')->default(0);
            $table->unsignedBigInteger('tax_total')->default(0);
            $table->unsignedBigInteger('grand_total');
            $table->foreignUuid('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('order_number');
            $table->index('user_id');
            $table->index('order_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
