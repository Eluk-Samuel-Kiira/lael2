<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every money column that must be signed BIGINT.
     *
     * Two reasons a column needs to be signed:
     *  1. Outflows are stored as negatives so a plain SUM() returns the
     *     net change across the column.
     *  2. Refunds, returns, and below-cost sales can each produce a
     *     legitimately negative value on their own.
     *
     * Two reasons BIGINT (not INT):
     *  1. INT caps at ~2.1 billion — a single large order can overflow it.
     *  2. BIGINT gives ±9.2 quintillion, which is safe for any currency
     *     at any realistic multiplier.
     */
    private array $signedMoneyColumns = [
        // ── Logs that hold frozen per-unit + total pricing ──
        'inventory_transactions' => [
            'unit_cost_price',
            'unit_selling_price',
            'total_cost_value',
            'total_selling_value',
        ],
        'inventory_adjustments' => [
            'unit_cost_price',
            'unit_selling_price',
            'total_value_change',
        ],
        'single_shop_inventory_logs' => [
            'unit_cost_price',
            'unit_selling_price',
            'total_cost_value',
            'total_selling_value',
        ],
        'batch_logs' => [
            'unit_cost',
            'total_cost',
            'unit_selling_price',
            'total_selling_value',
            'gross_profit',
        ],

        // ── Order line costs (added by this migration) ──
        'order_items' => [
            'unit_cost_price',
            'total_cost_price',
            'gross_profit',
        ],

        // ── Serial-level pricing ──
        'serial_numbers' => [
            'supplier_cost_price',
            'total_shipping_cost',
            'ura_taxes_applied',
            'additional_expenses',
            'grand_total_cost_price',
            'selling_price',
            'discount_selling_price',
            'sold_unit_price',
            'sold_total_price',
            'sold_gross_profit',
        ],

        // ── Per-scope item pricing ──
        'inventory_items' => [
            'supplier_cost_price',
            'total_shipping_cost',
            'ura_taxes_applied',
            'additional_expenses',
            'grand_total_cost_price',
            'selling_price',
            'discount_selling_price',
        ],

        // ── Variant catalog pricing ──
        'product_variants' => [
            'supplier_cost_price',
            'total_shipping_cost',
            'ura_taxes_applied',
            'additional_expenses',
            'grand_total_cost_price',
            'selling_price',
            'discount_selling_price',
        ],
    ];

    public function up(): void
    {
        // ─────────────────────────────────────────────────────────
        // STEP 1 — Add the new order_items columns (signed BIGINT)
        // ─────────────────────────────────────────────────────────
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                if (! Schema::hasColumn('order_items', 'unit_cost_price')) {
                    $table->bigInteger('unit_cost_price')->nullable()->after('unit_price');
                }
                if (! Schema::hasColumn('order_items', 'total_cost_price')) {
                    $table->bigInteger('total_cost_price')->nullable()->after('total_price');
                }
                if (! Schema::hasColumn('order_items', 'gross_profit')) {
                    $table->bigInteger('gross_profit')->nullable()->after('total_cost_price');
                }
                if (! Schema::hasColumn('order_items', 'pricing_source')) {
                    $table->string('pricing_source', 20)->nullable()->after('gross_profit');
                }

                if (! $this->indexExists('order_items', 'idx_order_items_tenant_variant')) {
                    $table->index(['tenant_id', 'variant_id'], 'idx_order_items_tenant_variant');
                }
            });
        }

        // ─────────────────────────────────────────────────────────
        // STEP 2 — Convert every money column to signed BIGINT
        //
        //   This fixes the "Out of range value for column
        //   total_cost_value" error by dropping the UNSIGNED modifier
        //   from already-created columns. Safe to re-run — the loop
        //   skips tables/columns that don't exist.
        // ─────────────────────────────────────────────────────────
        foreach ($this->signedMoneyColumns as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($tableName, $column)) {
                    continue;
                }

                // Plain BIGINT (signed) — no UNSIGNED
                DB::statement(
                    "ALTER TABLE `{$tableName}` MODIFY `{$column}` BIGINT NULL"
                );
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_items')) {
            return;
        }

        Schema::table('order_items', function (Blueprint $table) {
            if ($this->indexExists('order_items', 'idx_order_items_tenant_variant')) {
                $table->dropIndex('idx_order_items_tenant_variant');
            }

            $table->dropColumn([
                'unit_cost_price',
                'total_cost_price',
                'gross_profit',
                'pricing_source',
            ]);
        });

        // We deliberately do NOT reverse the signed-BIGINT conversion
        // in down(): reverting to UNSIGNED would break every row that
        // currently holds a negative value. If you must roll back, do
        // it explicitly with raw SQL, having confirmed no row holds a
        // negative in the column you're reverting.
    }

    /**
     * Guard against duplicate index creation on re-runs.
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $dbName     = $connection->getDatabaseName();

        $row = $connection->selectOne(
            "SELECT COUNT(*) AS cnt
               FROM information_schema.statistics
              WHERE table_schema = ?
                AND table_name   = ?
                AND index_name   = ?",
            [$dbName, $table, $index]
        );

        return $row && (int) $row->cnt > 0;
    }
};