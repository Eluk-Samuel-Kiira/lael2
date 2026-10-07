<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_pricing_columns_to_single_shop_inventory_logs.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('single_shop_inventory_logs', function (Blueprint $table) {

            // ─── Frozen cost (unit + total) ─────────────────────────
            if (!Schema::hasColumn('single_shop_inventory_logs', 'unit_cost_price')) {
                $table->bigInteger('unit_cost_price')
                      ->nullable()
                      ->after('metadata')
                      ->comment('Frozen cost per unit at the time of movement (base currency)');
            }

            if (!Schema::hasColumn('single_shop_inventory_logs', 'total_cost_value')) {
                $table->bigInteger('total_cost_value')
                      ->nullable()
                      ->after('unit_cost_price')
                      ->comment('quantity_change × unit_cost_price (base currency)');
            }

            // ─── Frozen selling price (unit + total) ────────────────
            if (!Schema::hasColumn('single_shop_inventory_logs', 'unit_selling_price')) {
                $table->bigInteger('unit_selling_price')
                      ->nullable()
                      ->after('total_cost_value')
                      ->comment('Frozen selling price per unit at the time of movement (base currency)');
            }

            if (!Schema::hasColumn('single_shop_inventory_logs', 'total_selling_value')) {
                $table->bigInteger('total_selling_value')
                      ->nullable()
                      ->after('unit_selling_price')
                      ->comment('quantity_change × unit_selling_price (base currency)');
            }

            // ─── Source of the frozen pricing ───────────────────────
            if (!Schema::hasColumn('single_shop_inventory_logs', 'pricing_source')) {
                $table->string('pricing_source', 40)
                      ->nullable()
                      ->after('total_selling_value')
                      ->comment('variant | item | batch | serial | log');
            }
        });
    }

    public function down(): void
    {
        Schema::table('single_shop_inventory_logs', function (Blueprint $table) {
            $cols = [
                'unit_cost_price',
                'total_cost_value',
                'unit_selling_price',
                'total_selling_value',
                'pricing_source',
            ];

            foreach ($cols as $col) {
                if (Schema::hasColumn('single_shop_inventory_logs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};