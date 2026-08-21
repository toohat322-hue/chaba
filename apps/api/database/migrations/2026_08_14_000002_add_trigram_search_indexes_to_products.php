<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// PRD §7.4/§14: typo-tolerant, multilingual search via Postgres pg_trgm
// (already enabled in Phase 1) instead of standing up Elasticsearch/Meilisearch
// before the catalog is large enough to need it.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX products_name_ar_trgm_idx ON products USING GIN (name_ar gin_trgm_ops)');
        DB::statement('CREATE INDEX products_name_fr_trgm_idx ON products USING GIN (name_fr gin_trgm_ops)');
        DB::statement('CREATE INDEX products_name_en_trgm_idx ON products USING GIN (name_en gin_trgm_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS products_name_ar_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS products_name_fr_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS products_name_en_trgm_idx');
    }
};
