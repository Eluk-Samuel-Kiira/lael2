<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. purchase_receipt_items ──────────────────────────────
        Schema::table('purchase_receipt_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_receipt_items', 'paid_total')) {
                $table->unsignedInteger('paid_total')
                      ->default(0)
                      ->after('unit_cost');
            }
            if (!Schema::hasColumn('purchase_receipt_items', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'partial', 'paid', 'overdue'])
                      ->default('pending')
                      ->after('paid_total');
            }
            if (!Schema::hasColumn('purchase_receipt_items', 'payment_method_id')) {
                $table->unsignedBigInteger('payment_method_id')
                      ->nullable()
                      ->after('payment_status');
            }
            if (!Schema::hasColumn('purchase_receipt_items', 'payment_date')) {
                $table->date('payment_date')
                      ->nullable()
                      ->after('payment_method_id');
            }
            if (!Schema::hasColumn('purchase_receipt_items', 'payment_transaction_id')) {
                $table->unsignedBigInteger('payment_transaction_id')
                      ->nullable()
                      ->after('payment_date');
            }
        });

        // ─── 2. purchase_order_items ────────────────────────────────
        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_order_items', 'paid_total')) {
                $table->unsignedInteger('paid_total')
                      ->default(0)
                      ->after('total_cost');
            }
        });

        // ─── 3. purchase_orders.payment_status default ──────────────
        // Only alter if the column exists and isn't already defaulting
        // to 'pending'. Wrapped in a try/catch because some DB engines
        // (SQLite in tests) don't support MODIFY COLUMN.
        if (Schema::hasColumn('purchase_orders', 'payment_status')) {
            try {
                DB::statement("
                    ALTER TABLE purchase_orders
                    MODIFY COLUMN payment_status
                    ENUM('pending','partial','paid','overdue')
                    NOT NULL DEFAULT 'pending'
                ");
            } catch (\Throwable $e) {
                // SQLite / non-MySQL: skip. The application-level default
                // in the model's $attributes array handles it.
            }

            // ─── 4. Backfill false 'paid' rows with no money behind them
            DB::table('purchase_orders')->where('payment_status', 'paid')->update([
                'payment_status' => 'pending',
            ]);

            // Then re-set 'paid' for the ones that actually have money
            DB::table('purchase_orders')
                ->whereRaw('COALESCE(total_paid, 0) >= COALESCE(received_total, total, 0)')
                ->whereRaw('COALESCE(received_total, total, 0) > 0')
                ->update(['payment_status' => 'paid']);

            // And 'partial' for the ones with some money but not full
            DB::table('purchase_orders')
                ->whereRaw('COALESCE(total_paid, 0) > 0')
                ->whereRaw('COALESCE(total_paid, 0) < COALESCE(received_total, total, 0)')
                ->update(['payment_status' => 'partial']);
        }

        // ─── 5. Backfill purchase_order_items.payment_status ────────
        if (Schema::hasColumn('purchase_order_items', 'paid_total')) {
            DB::statement("
                UPDATE purchase_order_items poi
                JOIN purchase_orders po ON po.id = poi.purchase_order_id
                SET poi.payment_status = CASE
                    WHEN poi.received_quantity <= 0                    THEN 'pending'
                    WHEN COALESCE(po.total_paid, 0) <= 0               THEN 'pending'
                    WHEN po.total_paid >= COALESCE(po.received_total, po.total, 0)
                         AND COALESCE(po.received_total, po.total, 0) > 0
                                                                       THEN 'paid'
                    ELSE                                                    'partial'
                END
            ");
        }
    }

    public function down(): void
    {
        // Intentionally empty: reverting would drop paid amounts from
        // historical receipts, which is destructive. If you need to roll
        // back, drop the columns manually after backing up the data.
    }
};