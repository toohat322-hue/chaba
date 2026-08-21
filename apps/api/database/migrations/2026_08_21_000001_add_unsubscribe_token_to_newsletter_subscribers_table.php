<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A per-subscriber token for the unsubscribe link in the new subscription-
// confirmation email — nullable+unique (Postgres allows multiple nulls in a
// unique column) so existing rows don't need a backfill; the application
// always generates one for every new subscription going forward.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->uuid('unsubscribe_token')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->dropColumn('unsubscribe_token');
        });
    }
};
