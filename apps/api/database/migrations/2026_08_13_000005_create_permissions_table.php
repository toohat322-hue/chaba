<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD §12: permissions — id (PK), key (e.g. `orders.edit`), description.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->string('key')->unique();
            $table->text('description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
