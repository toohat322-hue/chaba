<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12: communes — id (PK), wilaya_code (FK), name_ar, name_fr, name_en.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communes', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->string('wilaya_code', 2);
            $table->string('name_ar');
            $table->string('name_fr');
            $table->string('name_en');

            $table->foreign('wilaya_code')->references('code')->on('wilayas')->cascadeOnDelete();
            $table->index('wilaya_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communes');
    }
};
