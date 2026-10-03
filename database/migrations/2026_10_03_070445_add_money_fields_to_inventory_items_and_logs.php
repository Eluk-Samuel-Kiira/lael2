<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ────────────────────────────────────────────────────────────
        // 1. Inventory Items — per-location/department override pricing
        // ────────────────────────────────────────────────────────────
        Schema::table('inventory_items', function (Blueprint $table) {
            // Cost components (mirror ProductVariant's structure)
            $table->unsignedBigInteger('supplier_cost_price')->nullable()->after('preferred_stock_level');
            $table->unsignedBigInteger('total_shipping_cost')->nullable()->after('supplier_cost_price');
            $table->unsignedBigInteger('ura_taxes_applied')->nullable()->after('total_shipping_cost');
            $table->unsignedBigInteger('additional_expenses')->nullable()->after('ura_taxes_applied');
            $table->unsignedBigInteger('grand_total_cost_price')->nullable()->after('additional_expenses');

            // Selling side
            $table->unsignedBigInteger('selling_price')->nullable()->after('grand_total_cost_price');
            $table->unsignedBigInteger('discount_selling_price')->nullable()->after('selling_price');
            $table->decimal('discount_percentage', 8, 2)->nullable()->after('discount_selling_price');
            $table->decimal('markup_percentage', 8, 2)->nullable()->after('discount_percentage');

            // ★ Important flag — quickly tells you whether this item's pricing
            // was overridden or is inheriting from the variant.
            $table->boolean('has_custom_pricing')->default(false)->after('markup_percentage');

            // Helpful indexes for filter/report queries
            $table->index(['tenant_id', 'location_id', 'department_id'], 'idx_inv_pricing_scope');
        });

        // ────────────────────────────────────────────────────────────
        // 2. Inventory Adjustments — freeze prices at event time
        // ────────────────────────────────────────────────────────────
        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_cost_price')->nullable()->after('quantity_after');
            $table->unsignedBigInteger('unit_selling_price')->nullable()->after('unit_cost_price');
            $table->unsignedBigInteger('total_value_change')->nullable()->after('unit_selling_price');
        });

        // ────────────────────────────────────────────────────────────
        // 3. Inventory Transactions — freeze prices at event time
        // ────────────────────────────────────────────────────────────
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_cost_price')->nullable()->after('quantity');
            $table->unsignedBigInteger('unit_selling_price')->nullable()->after('unit_cost_price');
            $table->unsignedBigInteger('total_cost_value')->nullable()->after('unit_selling_price');
            $table->unsignedBigInteger('total_selling_value')->nullable()->after('total_cost_value');
        });

        // ────────────────────────────────────────────────────────────
        // 4. Single Shop Inventory Logs — used in single-shop tenants
        //    (Only if that table exists in your schema.)
        // ────────────────────────────────────────────────────────────
        if (Schema::hasTable('single_shop_inventory_logs')) {
            Schema::table('single_shop_inventory_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('single_shop_inventory_logs', 'unit_cost_price')) {
                    $table->unsignedBigInteger('unit_cost_price')->nullable()->after('quantity_after');
                }
                if (!Schema::hasColumn('single_shop_inventory_logs', 'unit_selling_price')) {
                    $table->unsignedBigInteger('unit_selling_price')->nullable()->after('unit_cost_price');
                }
                if (!Schema::hasColumn('single_shop_inventory_logs', 'total_value_change')) {
                    $table->unsignedBigInteger('total_value_change')->nullable()->after('unit_selling_price');
                }
            });
        }

        // ────────────────────────────────────────────────────────────
        // 5. Batch Logs — already carry unit_cost / total_cost, but
        //    add a selling-price column so per-batch profit can be
        //    reconstructed after the fact.
        // ────────────────────────────────────────────────────────────
        if (Schema::hasTable('batch_logs')) {
            Schema::table('batch_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('batch_logs', 'unit_selling_price')) {
                    $table->unsignedBigInteger('unit_selling_price')->nullable()->after('unit_cost');
                }
                if (!Schema::hasColumn('batch_logs', 'total_selling_value')) {
                    $table->unsignedBigInteger('total_selling_value')->nullable()->after('total_cost');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropIndex('idx_inv_pricing_scope');
            $table->dropColumn([
                'supplier_cost_price',
                'total_shipping_cost',
                'ura_taxes_applied',
                'additional_expenses',
                'grand_total_cost_price',
                'selling_price',
                'discount_selling_price',
                'discount_percentage',
                'markup_percentage',
                'has_custom_pricing',
            ]);
        });

        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->dropColumn(['unit_cost_price', 'unit_selling_price', 'total_value_change']);
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'unit_cost_price',
                'unit_selling_price',
                'total_cost_value',
                'total_selling_value',
            ]);
        });

        if (Schema::hasTable('single_shop_inventory_logs')) {
            Schema::table('single_shop_inventory_logs', function (Blueprint $table) {
                $table->dropColumn(['unit_cost_price', 'unit_selling_price', 'total_value_change']);
            });
        }

        if (Schema::hasTable('batch_logs')) {
            Schema::table('batch_logs', function (Blueprint $table) {
                $table->dropColumn(['unit_selling_price', 'total_selling_value']);
            });
        }
    }
};