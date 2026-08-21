<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Links a User to one external identity provider account (google/facebook/
// apple). One user can have several rows (one per linked provider); one
// provider identity can only ever point at one user (unique provider+
// provider_id) — that pairing is exactly how a repeat social login is
// recognized without re-matching by email every time.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_id');
            $table->string('provider_email')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
