<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PRD §7.14: a cart may carry one applied coupon code, previewed live on the
// cart page and carried through automatically when it converts to an order.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->string('coupon_code')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('coupon_code');
        });
    }
};
