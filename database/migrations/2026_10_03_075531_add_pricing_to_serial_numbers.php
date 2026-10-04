<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('serial_numbers')) {
            return;
        }

        Schema::table('serial_numbers', function (Blueprint $table) {
            // ★ Cost side
            if (! Schema::hasColumn('serial_numbers', 'supplier_cost_price')) {
                $table->unsignedBigInteger('supplier_cost_price')->nullable()->after('batch_id');
            }
            if (! Schema::hasColumn('serial_numbers', 'total_shipping_cost')) {
                $table->unsignedBigInteger('total_shipping_cost')->nullable()->after('supplier_cost_price');
            }
            if (! Schema::hasColumn('serial_numbers', 'ura_taxes_applied')) {
                $table->unsignedBigInteger('ura_taxes_applied')->nullable()->after('total_shipping_cost');
            }
            if (! Schema::hasColumn('serial_numbers', 'additional_expenses')) {
                $table->unsignedBigInteger('additional_expenses')->nullable()->after('ura_taxes_applied');
            }
            if (! Schema::hasColumn('serial_numbers', 'grand_total_cost_price')) {
                $table->unsignedBigInteger('grand_total_cost_price')->nullable()->after('additional_expenses');
            }

            // ★ Selling side
            if (! Schema::hasColumn('serial_numbers', 'selling_price')) {
                $table->unsignedBigInteger('selling_price')->nullable()->after('grand_total_cost_price');
            }
            if (! Schema::hasColumn('serial_numbers', 'discount_selling_price')) {
                $table->unsignedBigInteger('discount_selling_price')->nullable()->after('selling_price');
            }
            if (! Schema::hasColumn('serial_numbers', 'discount_percentage')) {
                $table->decimal('discount_percentage', 8, 2)->nullable()->after('discount_selling_price');
            }
            if (! Schema::hasColumn('serial_numbers', 'markup_percentage')) {
                $table->decimal('markup_percentage', 8, 2)->nullable()->after('discount_percentage');
            }

            // ★ Actual sale snapshot (what the customer actually paid)
            if (! Schema::hasColumn('serial_numbers', 'sold_unit_price')) {
                $table->unsignedBigInteger('sold_unit_price')->nullable()->after('markup_percentage');
            }
            if (! Schema::hasColumn('serial_numbers', 'sold_total_price')) {
                $table->unsignedBigInteger('sold_total_price')->nullable()->after('sold_unit_price');
            }
            if (! Schema::hasColumn('serial_numbers', 'sold_gross_profit')) {
                $table->bigInteger('sold_gross_profit')->nullable()->after('sold_total_price');
            }

            // ★ Audit
            if (! Schema::hasColumn('serial_numbers', 'pricing_source')) {
                $table->string('pricing_source', 20)->nullable()->after('sold_gross_profit');
                // 'variant' | 'item' | 'serial' | 'system'
            }
            if (! Schema::hasColumn('serial_numbers', 'has_custom_pricing')) {
                $table->boolean('has_custom_pricing')->default(false)->after('pricing_source');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('serial_numbers')) {
            return;
        }

        Schema::table('serial_numbers', function (Blueprint $table) {
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
                'sold_unit_price',
                'sold_total_price',
                'sold_gross_profit',
                'pricing_source',
                'has_custom_pricing',
            ]);
        });
    }
};