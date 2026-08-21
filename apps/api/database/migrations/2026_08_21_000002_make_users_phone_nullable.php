<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Social-login accounts (Google/Facebook/Apple) never supply a phone
// number, and every order already independently collects its own
// customer_phone/guest_phone at checkout (OrderService::checkout reads
// $data['customer_phone'], never $user->phone) — so the account-level
// phone was never actually load-bearing for order creation. Dropping only
// the NOT NULL constraint; the unique index is untouched (Postgres already
// allows multiple NULLs in a unique column) and RegisterRequest's own
// 'phone' => ['required', ...] rule is unaffected, since it's enforced at
// the request-validation layer, independent of DB nullability. No
// doctrine/dbal is installed, so this uses a raw statement rather than
// Blueprint::change() — same approach already used for the payment_method
// check-constraint migration.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE users ALTER COLUMN phone DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users ALTER COLUMN phone SET NOT NULL');
    }
};
