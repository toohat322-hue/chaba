<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12/§7.14: coupons — id (PK), code (unique, indexed), type
// (percentage/fixed/free_shipping/bxgy), value, min_order_value, start_date,
// end_date, usage_limit_total, usage_limit_per_customer, is_active.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->string('code')->unique();
            $table->enum('type', ['percentage', 'fixed', 'free_shipping', 'bxgy']);
            $table->unsignedBigInteger('value')->nullable();
            $table->unsignedBigInteger('min_order_value')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->unsignedInteger('usage_limit_total')->nullable();
            $table->unsignedInteger('usage_limit_per_customer')->nullable();
            $table->boolean('is_active')->default(true);

            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
