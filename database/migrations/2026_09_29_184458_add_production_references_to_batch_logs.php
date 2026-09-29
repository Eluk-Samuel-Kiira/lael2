<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add production FK columns (each guarded individually) ──
        if (!Schema::hasColumn('batch_logs', 'production_order_id')) {
            Schema::table('batch_logs', function (Blueprint $table) {
                $table->foreignId('production_order_id')
                    ->nullable()
                    ->after('purchase_receipt_id')
                    ->constrained('production_orders')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('batch_logs', 'production_order_input_id')) {
            Schema::table('batch_logs', function (Blueprint $table) {
                $table->foreignId('production_order_input_id')
                    ->nullable()
                    ->after('production_order_id')
                    ->constrained('production_order_inputs')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('batch_logs', 'production_order_output_id')) {
            Schema::table('batch_logs', function (Blueprint $table) {
                $table->foreignId('production_order_output_id')
                    ->nullable()
                    ->after('production_order_input_id')
                    ->constrained('production_order_outputs')
                    ->nullOnDelete();
            });
        }

        // ── 2. Widen the `type` enum, preserving every existing value ─
        if (DB::connection()->getDriverName() === 'mysql') {
            $this->widenBatchLogTypeEnum();
        }

        // ── 3. Add the composite index if missing ────────────────────
        $indexName = 'batch_logs_production_order_type_index';
        $hasIndex  = collect(DB::select("SHOW INDEX FROM batch_logs WHERE Key_name = ?", [$indexName]))->isNotEmpty();

        if (!$hasIndex) {
            Schema::table('batch_logs', function (Blueprint $table) use ($indexName) {
                $table->index(['production_order_id', 'type'], $indexName);
            });
        }
    }

    public function down(): void
    {
        $indexName = 'batch_logs_production_order_type_index';
        $hasIndex  = collect(DB::select("SHOW INDEX FROM batch_logs WHERE Key_name = ?", [$indexName]))->isNotEmpty();

        if ($hasIndex) {
            Schema::table('batch_logs', function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }

        if (Schema::hasColumn('batch_logs', 'production_order_output_id')) {
            Schema::table('batch_logs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('production_order_output_id');
            });
        }

        if (Schema::hasColumn('batch_logs', 'production_order_input_id')) {
            Schema::table('batch_logs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('production_order_input_id');
            });
        }

        if (Schema::hasColumn('batch_logs', 'production_order_id')) {
            Schema::table('batch_logs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('production_order_id');
            });
        }

        // Narrow the enum back — only safe if no rows use the new values.
        if (DB::connection()->getDriverName() === 'mysql') {
            $orphans = DB::table('batch_logs')
                ->whereNotIn('type', ['received', 'depleted', 'adjusted', 'transferred', 'expired'])
                ->count();

            if ($orphans === 0) {
                DB::statement("
                    ALTER TABLE batch_logs
                    MODIFY COLUMN type ENUM(
                        'received',
                        'depleted',
                        'adjusted',
                        'transferred',
                        'expired'
                    ) NOT NULL DEFAULT 'received'
                ");
            }
        }
    }

    /**
     * Widen the `type` enum by merging:
     *   - the values currently defined on the column
     *   - every value present in the data
     *   - the values we want to support
     */
    private function widenBatchLogTypeEnum(): void
    {
        // 1. Current enum definition from information_schema
        $column = DB::selectOne("
            SELECT COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = 'batch_logs'
              AND COLUMN_NAME  = 'type'
        ");

        $currentEnum = $column->COLUMN_TYPE ?? "enum('received')";

        preg_match_all("/'([^']+)'/", $currentEnum, $matches);
        $defined = $matches[1] ?? [];

        // 2. Values actually present in the table
        $inData = DB::table('batch_logs')->distinct()->pluck('type')->filter()->all();

        // 3. Values we want to add
        $wanted = ['received', 'depleted', 'adjusted', 'transferred', 'expired', 'assigned', 'unassigned', 'produced'];

        // 4. Merge everything, preserving order and uniqueness
        $all = collect($defined)
            ->merge($inData)
            ->merge($wanted)
            ->filter()
            ->unique()
            ->values()
            ->all();

        // 5. If nothing changed, skip the DDL entirely
        sort($defined);
        $sortedCurrent = $defined;

        sort($all);
        $sortedNew = $all;

        if ($sortedCurrent === $sortedNew) {
            return;
        }

        $values = collect($all)->map(fn($v) => "'" . addslashes($v) . "'")->implode(', ');

        DB::statement("
            ALTER TABLE batch_logs
            MODIFY COLUMN type ENUM({$values}) NOT NULL DEFAULT 'received'
        ");
    }
};