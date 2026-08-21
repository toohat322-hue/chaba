<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// pg_trgm/unaccent power the Postgres full-text search used in Phase 3 (§14);
// gen_random_uuid() (used as the default for every UUID primary key below) is
// built into Postgres core since v16, so no extension is needed for it.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
    }

    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS unaccent');
        DB::statement('DROP EXTENSION IF EXISTS pg_trgm');
    }
};
