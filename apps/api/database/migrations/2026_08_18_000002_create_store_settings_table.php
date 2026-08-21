<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Admin Settings (gap-audit item): a single-row table for store-wide
// configuration an admin can edit — name/contact info (also surfaced on the
// storefront's Contact page) and a tax rate actually consumed by
// OrderService::checkout (defaults to 0 so behavior is unchanged until an
// admin opts in). Payment gateway activation deliberately stays env-driven
// (see CIB_ENABLED/EDAHABIA_ENABLED in .env) rather than duplicated here —
// merchant secrets shouldn't live in a DB row readable by any settings.view
// grant; the settings page surfaces their status read-only instead.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->string('store_name');
            $table->string('support_email')->nullable();
            $table->string('support_phone')->nullable();
            $table->string('store_address')->nullable();
            // Basis points (1900 = 19%) to keep tax math in integers, same
            // convention as every money column elsewhere in this schema.
            $table->unsignedInteger('tax_rate_bps')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
