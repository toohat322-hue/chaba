<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12/§7.8: addresses — id (PK), user_id (FK users, nullable for guest-order
// snapshots), full_name, phone, wilaya_code (FK), commune_id (FK), address_line,
// landmark, postal_code (optional), is_default, created_at.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('phone');
            $table->string('wilaya_code', 2);
            $table->foreignUuid('commune_id')->constrained('communes')->restrictOnDelete();
            $table->string('address_line');
            $table->string('landmark')->nullable();
            $table->string('postal_code')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('wilaya_code')->references('code')->on('wilayas')->restrictOnDelete();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
