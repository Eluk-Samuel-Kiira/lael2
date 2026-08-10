<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_receipt_items', function (Blueprint $table) {
            // For batch-strategy variants, this IS the sellable stock ledger.
            // Starts equal to quantity_received; decremented as sales are
            // fulfilled from this specific batch (FIFO or otherwise —
            // depletion logic lives at the POS/sale layer, not here).
            // NULL for quantity-strategy variants, since they don't use
            // batch tracking at all — overal_quantity_at_hand is the source
            // of truth for those instead.
            $table->integer('quantity_remaining')->nullable()->after('quantity_received');

            // Batches are received without a department/location assignment
            // by default — "any department can claim from it" until it's
            // explicitly allocated. Left nullable here; allocation is a
            // separate feature/workflow, not part of receiving.
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete()->after('quantity_remaining');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete()->after('location_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_receipt_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('location_id');
            $table->dropColumn('quantity_remaining');
        });
    }
};