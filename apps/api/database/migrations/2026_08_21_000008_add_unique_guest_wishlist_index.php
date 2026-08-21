<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// wishlists' existing unique(['user_id', 'variant_id']) doesn't stop guest
// duplicates: user_id is NULL for guest wishlist rows, and Postgres treats
// NULL as distinct from NULL in a unique index, so the same session_token
// could wishlist the same variant unlimited times. Adds the matching partial
// unique index for the guest case; logged-in users are already covered.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            DELETE FROM wishlists WHERE id IN (
                SELECT id FROM (
                    SELECT id, ROW_NUMBER() OVER (
                        PARTITION BY session_token, variant_id ORDER BY created_at DESC
                    ) AS rn
                    FROM wishlists WHERE user_id IS NULL
                ) ranked WHERE rn > 1
            )
        SQL);

        DB::statement(
            'CREATE UNIQUE INDEX wishlists_guest_session_variant_unique ON wishlists (session_token, variant_id) WHERE user_id IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS wishlists_guest_session_variant_unique');
    }
};
