<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// ReviewService::submit() relies solely on Review::updateOrCreate(['product_id',
// 'user_id'], ...) inside a transaction with no row lock — under Postgres'
// default READ COMMITTED isolation, two concurrent submits (double-click, two
// tabs) can both miss each other's uncommitted insert and create two review
// rows for the same user+product, double-counting into the average rating.
// Backs the "one review per user per product" rule with a real constraint
// (the app-level lock added in ReviewService is the primary fix; this is the
// defense-in-depth backstop). Keeps the most recently created row for any
// pre-existing duplicate before the index is created.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            DELETE FROM reviews WHERE id IN (
                SELECT id FROM (
                    SELECT id, ROW_NUMBER() OVER (
                        PARTITION BY product_id, user_id ORDER BY created_at DESC
                    ) AS rn
                    FROM reviews
                ) ranked WHERE rn > 1
            )
        SQL);

        DB::statement('CREATE UNIQUE INDEX reviews_product_user_unique ON reviews (product_id, user_id)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS reviews_product_user_unique');
    }
};
