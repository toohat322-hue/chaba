<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Backs atomic order-number generation (CHB-{year}-{6-digit nextval}).
// A Postgres sequence avoids the race condition of counting existing rows
// under concurrent checkouts — nextval() is atomic without any app-level lock.
return new class extends Migration
{
    public function up(): void
    {
        // IF NOT EXISTS: migrate:fresh's DROP SCHEMA CASCADE removes tables
        // but a bare CREATE SEQUENCE isn't owned by any table, so repeated
        // fresh-migrate cycles (e.g. across separate test-suite runs against
        // the same chaba_test database) can otherwise collide with a
        // leftover sequence from a prior run.
        DB::statement('CREATE SEQUENCE IF NOT EXISTS order_number_seq');
    }

    public function down(): void
    {
        DB::statement('DROP SEQUENCE IF EXISTS order_number_seq');
    }
};
