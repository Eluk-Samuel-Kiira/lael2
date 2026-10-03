<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────────────────
        // 1. Purchase Receipt Items (batches)
        //    Add selling-side pricing + a has_custom_pricing flag
        //    so the POS can tell when a batch genuinely overrides
        //    the variant's default price.
        // ─────────────────────────────────────────────────────────
        if (Schema::hasTable('purchase_receipt_items')) {
            Schema::table('purchase_receipt_items', function (Blueprint $table) {
                if (! Schema::hasColumn('purchase_receipt_items', 'grand_total_cost_price')) {
                    $table->bigInteger('grand_total_cost_price')->nullable()->after('unit_cost');
                }
                if (! Schema::hasColumn('purchase_receipt_items', 'unit_selling_price')) {
                    $table->bigInteger('unit_selling_price')->nullable()->after('grand_total_cost_price');
                }
                if (! Schema::hasColumn('purchase_receipt_items', 'discount_selling_price')) {
                    $table->bigInteger('discount_selling_price')->nullable()->after('unit_selling_price');
                }
                if (! Schema::hasColumn('purchase_receipt_items', 'discount_percentage')) {
                    $table->decimal('discount_percentage', 8, 2)->nullable()->after('discount_selling_price');
                }
                if (! Schema::hasColumn('purchase_receipt_items', 'pricing_source')) {
                    $table->string('pricing_source', 20)->nullable()->after('discount_percentage');
                }
                if (! Schema::hasColumn('purchase_receipt_items', 'has_custom_pricing')) {
                    // ★ Opt-in flag — batches only override the variant
                    //   price when this is set. Absent / 0 means the batch
                    //   inherits its price from the inventory item or variant.
                    $table->boolean('has_custom_pricing')->default(0)->after('pricing_source');
                }

                // Guard the index — self-contained so it can't crash on re-run
                if (! $this->indexExists('purchase_receipt_items', 'idx_pri_tenant_scope')) {
                    $table->index(
                        ['tenant_id', 'location_id', 'department_id'],
                        'idx_pri_tenant_scope'
                    );
                }
            });

            // Ensure any existing unit_cost is signed BIGINT
            DB::statement("ALTER TABLE `purchase_receipt_items` MODIFY `unit_cost` BIGINT NULL");
        }

        // ─────────────────────────────────────────────────────────
        // 2. Recipes (header)
        //    Snapshot the recipe's unit cost so reports don't have
        //    to recompute from ingredients on every read.
        // ─────────────────────────────────────────────────────────
        if (Schema::hasTable('recipes')) {
            Schema::table('recipes', function (Blueprint $table) {
                if (! Schema::hasColumn('recipes', 'unit_cost')) {
                    $table->bigInteger('unit_cost')->nullable()->after('product_id');
                }
                if (! Schema::hasColumn('recipes', 'unit_selling_price')) {
                    $table->bigInteger('unit_selling_price')->nullable()->after('unit_cost');
                }
                if (! Schema::hasColumn('recipes', 'last_costed_at')) {
                    $table->timestamp('last_costed_at')->nullable()->after('unit_selling_price');
                }
            });
        }

        // ─────────────────────────────────────────────────────────
        // 3. Recipe Ingredients (lines)
        //    Snapshot the ingredient's per-unit cost + total cost,
        //    and add the same opt-in flag so a line can override
        //    its own price.
        // ─────────────────────────────────────────────────────────
        if (Schema::hasTable('recipe_ingredients')) {
            Schema::table('recipe_ingredients', function (Blueprint $table) {
                if (! Schema::hasColumn('recipe_ingredients', 'unit_cost')) {
                    $table->bigInteger('unit_cost')->nullable()->after('quantity_required');
                }
                if (! Schema::hasColumn('recipe_ingredients', 'total_cost')) {
                    $table->bigInteger('total_cost')->nullable()->after('unit_cost');
                }
                if (! Schema::hasColumn('recipe_ingredients', 'unit_selling_price')) {
                    $table->bigInteger('unit_selling_price')->nullable()->after('total_cost');
                }
                if (! Schema::hasColumn('recipe_ingredients', 'pricing_source')) {
                    $table->string('pricing_source', 20)->nullable()->after('unit_selling_price');
                }
                if (! Schema::hasColumn('recipe_ingredients', 'has_custom_pricing')) {
                    // ★ Same opt-in pattern as the other strategies.
                    $table->boolean('has_custom_pricing')->default(0)->after('pricing_source');
                }
            });
        }

        // ─────────────────────────────────────────────────────────
        // 4. Batch Logs
        //    Freeze the selling side + gross profit on every batch
        //    movement so historical reports are truthful even after
        //    the variant is repriced.
        // ─────────────────────────────────────────────────────────
        if (Schema::hasTable('batch_logs')) {
            Schema::table('batch_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('batch_logs', 'unit_selling_price')) {
                    $table->bigInteger('unit_selling_price')->nullable()->after('unit_cost');
                }
                if (! Schema::hasColumn('batch_logs', 'total_selling_value')) {
                    $table->bigInteger('total_selling_value')->nullable()->after('total_cost');
                }
                if (! Schema::hasColumn('batch_logs', 'gross_profit')) {
                    // Signed — a below-cost sale produces negative profit
                    $table->bigInteger('gross_profit')->nullable()->after('total_selling_value');
                }
                if (! Schema::hasColumn('batch_logs', 'pricing_source')) {
                    $table->string('pricing_source', 20)->nullable()->after('gross_profit');
                }
            });

            // Normalise money columns to signed BIGINT
            // (unit_cost and total_cost may have been created as UNSIGNED
            // in an earlier migration — negatives are needed for outflows)
            DB::statement("ALTER TABLE `batch_logs` MODIFY `unit_cost`           BIGINT NULL");
            DB::statement("ALTER TABLE `batch_logs` MODIFY `total_cost`          BIGINT NULL");
            DB::statement("ALTER TABLE `batch_logs` MODIFY `unit_selling_price`  BIGINT NULL");
            DB::statement("ALTER TABLE `batch_logs` MODIFY `total_selling_value` BIGINT NULL");
            DB::statement("ALTER TABLE `batch_logs` MODIFY `gross_profit`        BIGINT NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_receipt_items')) {
            Schema::table('purchase_receipt_items', function (Blueprint $table) {
                if ($this->indexExists('purchase_receipt_items', 'idx_pri_tenant_scope')) {
                    $table->dropIndex('idx_pri_tenant_scope');
                }

                $table->dropColumn([
                    'grand_total_cost_price',
                    'unit_selling_price',
                    'discount_selling_price',
                    'discount_percentage',
                    'pricing_source',
                    'has_custom_pricing',      // ★ new
                ]);
            });
        }

        if (Schema::hasTable('recipes')) {
            Schema::table('recipes', function (Blueprint $table) {
                $table->dropColumn([
                    'unit_cost',
                    'unit_selling_price',
                    'last_costed_at',
                ]);
            });
        }

        if (Schema::hasTable('recipe_ingredients')) {
            Schema::table('recipe_ingredients', function (Blueprint $table) {
                $table->dropColumn([
                    'unit_cost',
                    'total_cost',
                    'unit_selling_price',
                    'pricing_source',
                    'has_custom_pricing',      // ★ new
                ]);
            });
        }

        if (Schema::hasTable('batch_logs')) {
            Schema::table('batch_logs', function (Blueprint $table) {
                $table->dropColumn([
                    'unit_selling_price',
                    'total_selling_value',
                    'gross_profit',
                    'pricing_source',
                ]);
            });
        }
    }

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