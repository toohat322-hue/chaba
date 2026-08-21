<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The Phase-1 audit_logs table (2026_08_13_000033) was scaffolded but never
// wired up to any controller — its `entity_type`/`entity_id` polymorphic
// reference works for looking a subject up live, but that lookup fails
// silently once the subject is later hard-deleted (e.g. a removed product
// variant), losing exactly the context an accountability log exists to
// preserve. actor_name/subject_label snapshot at write time instead,
// matching the *_snapshot convention already used on order_items.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('actor_name')->after('actor_id');
            $table->string('subject_label')->after('entity_id');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['actor_name', 'subject_label']);
        });
    }
};
