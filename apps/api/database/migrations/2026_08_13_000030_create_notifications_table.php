<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12/§7.15: notifications — id (PK), user_id (FK, nullable for guest via
// phone), channel (in_app/email/sms/whatsapp/push), event_type, payload
// (jsonb), status (queued/sent/failed), sent_at, created_at.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('channel', ['in_app', 'email', 'sms', 'whatsapp', 'push']);
            $table->string('event_type');
            $table->jsonb('payload')->nullable();
            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
