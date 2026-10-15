<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen every `notes` column that now receives multi-line audit
     * narratives from VARCHAR(255) to TEXT.
     *
     * Every table in this list is written to by one of the audit-trail
     * sweeps (Expense, PurchaseOrder, ProductionOrder, InventoryAdjustments,
     * BatchController, POSController, etc.). At 255 characters, a single
     * appended line plus a couple of subsequent status changes will
     * silently truncate. TEXT gives 65,535 bytes — enough for hundreds of
     * timestamped lines.
     *
     * The migration is IDEMPOTENT:
     *   - Tables that don't exist are skipped.
     *   - Columns that don't exist are skipped (with a warning to the log).
     *   - Columns that are already TEXT are skipped — no needless ALTER.
     *
     * The `down()` is a NO-OP on purpose. Widening a text column is a
     * one-way move; shrinking it back to VARCHAR(255) will fail the
     * moment any row holds more than 255 characters, and MySQL will
     * refuse the ALTER with:
     *
     *     SQLSTATE[22001]: String data, right truncated:
     *     1406 Data too long for column 'notes'
     *
     * Rolling back is done manually, in a maintenance window, after
     * exporting every `notes` value longer than 255 characters.
     */
    public function up(): void
    {
        $tables = [
            'expenses',
            'purchase_orders',
            'purchase_receipts',
            'received_product_variants',
            'supplier_tax_liabilities',
            'batch_logs',
            'single_shop_inventory_logs',
            'inventory_adjustments',
            'inventory_transactions',
        ];

        foreach ($tables as $table) {
            // ── Skip if the table doesn't exist ──────────────────────
            if (! Schema::hasTable($table)) {
                \Log::info("[migration] Skipping {$table}: table does not exist.");
                continue;
            }

            // ── Skip if the column doesn't exist ─────────────────────
            if (! Schema::hasColumn($table, 'notes')) {
                \Log::info("[migration] Skipping {$table}.notes: column does not exist.");
                continue;
            }

            // ── Skip if it's already TEXT/LONGTEXT ───────────────────
            $currentType = strtolower(Schema::getColumnType($table, 'notes'));
            if (in_array($currentType, ['text', 'mediumtext', 'longtext', 'tinytext'], true)) {
                \Log::info("[migration] Skipping {$table}.notes: already {$currentType}.");
                continue;
            }

            // ── Widen it ─────────────────────────────────────────────
            Schema::table($table, function (Blueprint $t) {
                $t->text('notes')->nullable()->change();
            });

            \Log::info("[migration] Widened {$table}.notes to TEXT.");
        }
    }

    /**
     * No-op on purpose.
     *
     * Reverting this migration by shrinking `notes` back to
     * VARCHAR(255) is destructive: any row with more than 255
     * characters in `notes` will cause MySQL to reject the ALTER
     * with a "Data too long for column 'notes'" error.
     *
     * If you genuinely need to reverse the widening:
     *
     *   1. Export the full contents of `notes` for every affected table:
     *
     *          CREATE TABLE expenses_notes_archive AS
     *          SELECT id, notes FROM expenses WHERE CHAR_LENGTH(notes) > 255;
     *
     *   2. Truncate in place:
     *
     *          UPDATE expenses SET notes = LEFT(notes, 255)
     *          WHERE CHAR_LENGTH(notes) > 255;
     *
     *   3. Then manually run the ALTER:
     *
     *          ALTER TABLE expenses MODIFY notes VARCHAR(255) NULL;
     *
     *   4. Repeat per table.
     *
     * None of that belongs in an automatic migration.
     */
    public function down(): void
    {
        // Intentionally empty.
    }
};