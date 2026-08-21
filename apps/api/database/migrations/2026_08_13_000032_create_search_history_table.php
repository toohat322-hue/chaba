<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12: search_history — id (PK), user_id (FK, nullable), session_token,
// query, result_count, created_at.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_history', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_token')->nullable();
            $table->string('query');
            $table->unsignedInteger('result_count')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['session_token', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_history');
    }
};
