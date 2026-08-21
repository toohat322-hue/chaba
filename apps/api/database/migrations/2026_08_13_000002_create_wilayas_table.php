<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PRD §12: wilayas — code (PK, 01–58), name_ar, name_fr, name_en.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayas', function (Blueprint $table) {
            $table->string('code', 2)->primary();
            $table->string('name_ar');
            $table->string('name_fr');
            $table->string('name_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayas');
    }
};
