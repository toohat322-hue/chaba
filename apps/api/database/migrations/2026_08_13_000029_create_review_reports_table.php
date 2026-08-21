<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12/§7.12: review_reports — id (PK), review_id (FK), reporter_user_id
// (FK), reason, created_at.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_reports', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('review_id')->constrained('reviews')->cascadeOnDelete();
            $table->foreignUuid('reporter_user_id')->constrained('users')->restrictOnDelete();
            $table->string('reason');
            $table->timestamp('created_at')->useCurrent();

            $table->index('review_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_reports');
    }
};
