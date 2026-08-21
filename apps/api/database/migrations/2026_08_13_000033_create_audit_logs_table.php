<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12/§15: audit_logs — id (PK), actor_id (FK users), action, entity_type,
// entity_id, before (jsonb), after (jsonb), ip_address, created_at.
// entity_id/entity_type form a polymorphic reference (no FK constraint) since
// audit_logs records changes across every admin-writable entity in the system.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('action');
            $table->string('entity_type');
            $table->uuid('entity_id');
            $table->jsonb('before')->nullable();
            $table->jsonb('after')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_type', 'entity_id']);
            $table->index('actor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
