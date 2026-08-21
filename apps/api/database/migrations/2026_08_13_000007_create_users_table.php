<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12: users — id (PK), full_name, phone (unique, required), email (unique,
// optional), password_hash, role_id (FK roles, null for customers),
// preferred_language (ar/fr/en), is_guest, phone_verified_at, email_verified_at,
// status (active/blocked), created_at, updated_at. Index: phone, email.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->string('full_name');
            $table->string('phone')->unique();
            $table->string('email')->unique()->nullable();
            $table->string('password_hash')->nullable();
            $table->foreignUuid('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->enum('preferred_language', ['ar', 'fr', 'en'])->default('ar');
            $table->boolean('is_guest')->default(false);
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->enum('status', ['active', 'blocked'])->default('active');
            $table->timestamps();

            $table->index('phone');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
