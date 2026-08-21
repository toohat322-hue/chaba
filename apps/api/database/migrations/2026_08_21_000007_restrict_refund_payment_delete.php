<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Refund.php documents refunds as "an immutable ledger entry, never edited
// after the fact" — but the FK was cascadeOnDelete, so deleting a Payment
// row silently wiped every Refund tied to it. Nothing else in the schema
// protects a Payment from direct deletion, so this closes a real path to
// destroying financial records. restrictOnDelete matches how payments.order_id
// already behaves.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->foreign('payment_id')->references('id')->on('payments')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->foreign('payment_id')->references('id')->on('payments')->cascadeOnDelete();
        });
    }
};
