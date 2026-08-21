<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PRD §25 Phase 6: the original schema only tracked delivery status
// (queued/sent/failed) — an in-app "mark as read"/unread-count inbox needs
// its own, orthogonal read state (a notification can be sent AND unread, or
// sent AND read; delivery and read state don't collapse into one column).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable()->after('sent_at');

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'read_at']);
            $table->dropColumn('read_at');
        });
    }
};
