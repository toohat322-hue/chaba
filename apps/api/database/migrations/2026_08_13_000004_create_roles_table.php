<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12/§19.1: roles — id (PK), name (8 admin roles), description.
// Seeding the 8 roles themselves is a Phase 2 (RBAC scaffolding) deliverable;
// this migration only creates the table structure.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->string('name')->unique();
            $table->text('description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
