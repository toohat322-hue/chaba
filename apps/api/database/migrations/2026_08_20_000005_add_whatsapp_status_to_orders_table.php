<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Best-effort tracking of whether the customer's browser actually attempted
// the wa.me redirect ('pending'/'opened') — deliberately separate from
// order_status, which only a human admin (or a future real webhook) ever
// advances. A plain string, not a DB enum: only two app-level values, and
// this mirrors no existing enum-column precedent worth matching.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('whatsapp_status')->default('pending');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('whatsapp_status');
        });
    }
};
