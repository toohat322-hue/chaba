<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Widens the two CHECK constraints Laravel's enum() generated on Postgres
// (confirmed via pg_get_constraintdef: orders_payment_method_check,
// payments_provider_check) to allow 'whatsapp' alongside cod/cib/edahabia.
// Laravel has no schema-builder verb for altering an existing enum/check
// list, so this drops and re-adds each constraint directly.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE orders DROP CONSTRAINT orders_payment_method_check');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_method_check CHECK (payment_method IN ('cod', 'cib', 'edahabia', 'whatsapp'))");

        DB::statement('ALTER TABLE payments DROP CONSTRAINT payments_provider_check');
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_provider_check CHECK (provider IN ('cod', 'cib', 'edahabia', 'whatsapp'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE orders DROP CONSTRAINT orders_payment_method_check');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_method_check CHECK (payment_method IN ('cod', 'cib', 'edahabia'))");

        DB::statement('ALTER TABLE payments DROP CONSTRAINT payments_provider_check');
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_provider_check CHECK (provider IN ('cod', 'cib', 'edahabia'))");
    }
};
