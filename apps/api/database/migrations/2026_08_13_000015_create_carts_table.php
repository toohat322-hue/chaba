<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12: carts — id (PK), user_id (FK, nullable for guest), session_token
// (guest carts), status (active/converted/abandoned), created_at, updated_at.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('session_token')->nullable();
            $table->enum('status', ['active', 'converted', 'abandoned'])->default('active');
            $table->timestamps();

            $table->index('user_id');
            $table->index('session_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
