<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// CouponService::findValid() has always matched codes case-insensitively
// (upper(code) = ?), but the column's unique constraint was plain
// case-sensitive — "SAVE10" and "save10" could both be created as "unique"
// rows and then collide unpredictably at lookup/checkout time. Normalizes
// existing data to uppercase (Coupon::code is now uppercased on write too,
// see the model) and backs it with a real case-insensitive unique index.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('UPDATE coupons SET code = UPPER(code)');
        DB::statement('CREATE UNIQUE INDEX coupons_code_upper_unique ON coupons (UPPER(code))');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS coupons_code_upper_unique');
    }
};
