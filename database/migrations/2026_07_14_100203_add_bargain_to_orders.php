<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // The order's total BEFORE any bargain discount was ever applied.
            // Set once, on first discount application, and never re-derived
            // from $order->total again — this is what prevents a discount
            // from compounding on retry/duplicate submission.
            // ✅ Stored in smallest currency unit (e.g., cents)
            $table->bigInteger('subtotal_before_bargain')->nullable()->after('discount_total')
                ->comment('Order total before bargain discount in smallest currency unit');

            // The bargain-discount component of discount_total, tracked
            // separately from any item-level/promotion discounts, so a
            // resubmitted bargain discount REPLACES rather than ADDS.
            // ✅ Stored in smallest currency unit (e.g., cents)
            $table->bigInteger('bargain_discount_applied')->default(0)->after('subtotal_before_bargain')
                ->comment('Bargain discount amount in smallest currency unit');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal_before_bargain', 'bargain_discount_applied']);
        });
    }
};