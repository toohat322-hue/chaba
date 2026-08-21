<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Additive gap-fill: the catalog spec asks for GET /featured-products, but
// PRD §12's products table has no such flag. Safe/non-destructive addition.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('featured')->default(false)->after('status');
            $table->index('featured');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['featured']);
            $table->dropColumn('featured');
        });
    }
};
