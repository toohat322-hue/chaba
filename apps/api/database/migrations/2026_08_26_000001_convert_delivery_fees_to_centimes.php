<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// One-time data fix: the admin delivery-fee form (DeliveryFeeRow.tsx) sent
// whatever whole-dinar number the admin typed straight through with no
// DA -> centimes conversion, unlike every other price input in the app
// (see PriceInput.tsx / ProductForm.tsx). So a fee meant to be "600 DA" was
// stored as the integer 600 — which this app's pricing convention (PRD A6,
// integer centimes everywhere) reads as 6 DA. The form is now fixed
// (commit ea76be8); this corrects rows saved before that fix. Confirmed
// with the store owner that every existing delivery_fees.fee value was
// entered through the broken form, so a blanket x100 is safe here — this
// is NOT a general-purpose pattern to repeat without that same confirmation.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('delivery_fees')->update(['fee' => DB::raw('fee * 100')]);
    }

    public function down(): void
    {
        DB::table('delivery_fees')->update(['fee' => DB::raw('fee / 100')]);
    }
};
