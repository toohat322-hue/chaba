<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Marks a role as "an admin has edited its permissions" so
// RolePermissionSeeder (which runs on every deploy, via preDeployCommand)
// can tell a customized role apart from one still on the PRD-default grant
// set — the same class of bug DeliveryFeeSeeder had (see the
// 2026_08_26_000001 migration): re-seeding hardcoded defaults on every
// deploy silently wiped out whatever an admin had just configured.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->timestamp('permissions_customized_at')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('permissions_customized_at');
        });
    }
};
