<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Per-payment refund tracking ──────────────────────
        if (!Schema::hasColumn('order_payments', 'refunded_amount')) {
            Schema::table('order_payments', function (Blueprint $table) {
                $table->unsignedBigInteger('refunded_amount')
                      ->default(0)
                      ->after('amount')
                      ->comment('How much of this payment has been refunded (base currency, integer).');
            });
        }

        // ── Per-item refund tracking ─────────────────────────
        if (!Schema::hasColumn('order_items', 'refunded_quantity')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->decimal('refunded_quantity', 15, 4)
                      ->default(0)
                      ->after('quantity')
                      ->comment('Cumulative quantity of this line item that has been refunded.');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('order_payments', 'refunded_amount')) {
            Schema::table('order_payments', function (Blueprint $table) {
                $table->dropColumn('refunded_amount');
            });
        }
        if (Schema::hasColumn('order_items', 'refunded_quantity')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('refunded_quantity');
            });
        }
    }
};