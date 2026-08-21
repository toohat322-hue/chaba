<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tracks whether an abandoned-cart reminder email has already gone out for
// this cart, so the scheduled command never emails the same cart twice.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
    }
};
