<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Gap-audit cleanup: the `promotions` table (2026_08_13_000026) was created
// but never got a Model, Controller, or any code reference anywhere in the
// app — pure dead schema. Rather than silently deleting the original
// historical migration (which would desync `migrate:status` on any
// environment where it already ran), this migration formally reverses it and
// removes the two permission keys ('promotions.view'/'promotions.edit') that
// only ever existed to gate the feature that was never built. Deleting the
// permission rows cascades into role_permissions (see
// 2026_08_13_000006_create_role_permissions_table's cascadeOnDelete), so no
// role is left holding a grant to a permission that no longer exists.
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('promotions');

        DB::table('permissions')->whereIn('key', ['promotions.view', 'promotions.edit'])->delete();
    }

    public function down(): void
    {
        // Intentionally not reversible: recreating the table doesn't restore
        // the feature that never existed. If promotions are built for real,
        // that's a fresh migration, not a rollback of this one.
    }
};
