<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §7.8: wilaya-based delivery fees, configurable in admin. No fee data
// existed anywhere in the Phase 1 schema — this is new. Seeded at 0 for
// `home` only (Checkout & Orders phase); admin fills in real courier rates.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_fees', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->string('wilaya_code', 2);
            $table->enum('delivery_method', ['home', 'pickup']);
            $table->unsignedBigInteger('fee')->default(0);
            $table->unsignedSmallInteger('eta_min_days')->nullable();
            $table->unsignedSmallInteger('eta_max_days')->nullable();
            $table->timestamps();

            $table->foreign('wilaya_code')->references('code')->on('wilayas')->cascadeOnDelete();
            $table->unique(['wilaya_code', 'delivery_method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_fees');
    }
};
