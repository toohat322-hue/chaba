<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// CartService::resolveCart() does a select-then-insert (firstOrCreate) with
// no DB constraint behind it — two concurrent requests with no existing
// active cart (two tabs, or a client retrying a failed request) can both
// insert a new active cart, splitting later item adds across two rows and
// silently losing whichever one isn't used at checkout. Partial (status =
// 'active') so a user/session can still have many non-active (converted/
// abandoned) carts, only ever one *active* one at a time — the same
// constraint resolveCart() now relies on via Cart::createOrFirst().
//
// Cleans up any pre-existing duplicates first (keeping the most recently
// touched one) so the new index can actually be created.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            UPDATE carts SET status = 'abandoned'
            WHERE status = 'active' AND user_id IS NOT NULL AND id NOT IN (
                SELECT DISTINCT ON (user_id) id FROM carts
                WHERE status = 'active' AND user_id IS NOT NULL
                ORDER BY user_id, updated_at DESC
            )
        SQL);

        DB::statement(<<<'SQL'
            UPDATE carts SET status = 'abandoned'
            WHERE status = 'active' AND session_token IS NOT NULL AND id NOT IN (
                SELECT DISTINCT ON (session_token) id FROM carts
                WHERE status = 'active' AND session_token IS NOT NULL
                ORDER BY session_token, updated_at DESC
            )
        SQL);

        DB::statement(
            "CREATE UNIQUE INDEX carts_active_user_unique ON carts (user_id) WHERE status = 'active' AND user_id IS NOT NULL"
        );
        DB::statement(
            "CREATE UNIQUE INDEX carts_active_session_unique ON carts (session_token) WHERE status = 'active' AND session_token IS NOT NULL"
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS carts_active_user_unique');
        DB::statement('DROP INDEX IF EXISTS carts_active_session_unique');
    }
};
