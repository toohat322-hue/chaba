<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Lets the admin toggle WhatsApp ordering independently of whatsapp_active
// (which only gates the storefront's contact FAB) — the same real
// whatsapp_number is reused for both, this just controls whether it's
// offered as a checkout payment method.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('whatsapp_orders_enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn('whatsapp_orders_enabled');
        });
    }
};
