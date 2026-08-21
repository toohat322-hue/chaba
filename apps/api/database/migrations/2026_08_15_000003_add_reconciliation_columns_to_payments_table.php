<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PRD §7.9 reconciliation ("admin Finance role reconciles remitted amounts
// against delivered-COD orders ... flagging discrepancies"). Additive to the
// existing payments table rather than a separate table — reconciliation is
// a status/annotation on a payment, not a distinct entity with its own
// lifecycle. `payments.view`/`payments.reconcile` permissions already exist
// (PermissionSeeder, seeded since Phase 2) and are granted to Finance
// Manager — this migration is what finally gives them something to act on.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('reconciliation_status', ['unreconciled', 'reconciled', 'disputed'])
                ->default('unreconciled')
                ->after('raw_response');
            $table->timestamp('reconciled_at')->nullable()->after('reconciliation_status');
            $table->foreignUuid('reconciled_by')->nullable()->after('reconciled_at')->constrained('users')->nullOnDelete();
            $table->text('reconciliation_notes')->nullable()->after('reconciled_by');

            $table->index('reconciliation_status');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reconciled_by');
            $table->dropColumn(['reconciliation_status', 'reconciled_at', 'reconciliation_notes']);
        });
    }
};
