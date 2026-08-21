<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Same snapshot discipline already applied to product_name_snapshot/
// sku_snapshot: frozen at checkout time so a later admin edit to the
// variant's size can never retroactively change what an existing order's
// receipt shows.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('size_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('size_snapshot');
        });
    }
};
