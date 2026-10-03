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
        //    Add selling-side pricing so a batch can be sold on its
        //    own cost/margin, independent of the variant's defaults.
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

                // Guard the index — self-contained so it can't crash on re-run
                if (! $this->indexExists('purchase_receipt_items', 'idx_pri_tenant_variant')) {
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
        //    Snapshot the ingredient's per-unit cost and total cost
        //    at the moment the recipe was built. Protects the recipe
        //    from variant repricing.
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
            });
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