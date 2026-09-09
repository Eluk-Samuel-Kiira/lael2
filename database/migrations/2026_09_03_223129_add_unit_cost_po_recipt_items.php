<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_receipt_items', function (Blueprint $table) {
            // Add unit_cost column to track the actual cost from supplier
            $table->decimal('unit_cost', 15, 2)->nullable()->after('quantity_remaining')
                ->comment('Actual unit cost from supplier at time of receipt');
            
            // Optional: Add an index for faster queries on unit_cost
            $table->index('unit_cost');
        });

        Schema::table('tenants', function (Blueprint $table) {
            // For MySQL - modify enum to include new values
            $table->enum('status', ['active', 'suspended', 'trial', 'inactive', 'expired'])
                  ->default('trial')
                  ->change();
        });

        Schema::table('billing_plans', function (Blueprint $table) {
            // 🔥 Reports Modules
            // $table->boolean('includes_financial_reports')->default(false)->after('includes_advanced_accounting');
            $table->boolean('includes_expense_reports')->default(false)->after('includes_financial_reports');
            $table->boolean('includes_order_reports')->default(false)->after('includes_expense_reports');
            $table->boolean('includes_product_reports')->default(false)->after('includes_order_reports');
            $table->boolean('includes_inventory_reports')->default(false)->after('includes_product_reports');
            $table->boolean('includes_purchasing_reports')->default(false)->after('includes_inventory_reports');
            $table->boolean('includes_production_reports')->default(false)->after('includes_purchasing_reports');
            $table->boolean('includes_restaurant_reports')->default(false)->after('includes_production_reports');
            $table->boolean('includes_sales_reports')->default(false)->after('includes_restaurant_reports');
            $table->boolean('includes_customer_reports')->default(false)->after('includes_sales_reports');
            $table->boolean('includes_supplier_reports')->default(false)->after('includes_customer_reports');
            
            // 🔥 Production Modules
            $table->boolean('includes_production_orders')->default(false)->after('includes_supplier_reports');
            $table->boolean('includes_bill_of_materials')->default(false)->after('includes_production_orders');
            $table->boolean('includes_work_orders')->default(false)->after('includes_bill_of_materials');
            
            // 🔥 Restaurant Modules
            $table->boolean('includes_restaurant_management')->default(false)->after('includes_work_orders');
            $table->boolean('includes_table_management')->default(false)->after('includes_restaurant_management');
            $table->boolean('includes_menu_management')->default(false)->after('includes_table_management');
            $table->boolean('includes_kitchen_display')->default(false)->after('includes_menu_management');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_receipt_items', function (Blueprint $table) {
            $table->dropIndex(['unit_cost']);
            $table->dropColumn('unit_cost');
        });
        
        Schema::table('tenants', function (Blueprint $table) {
            // Rollback to original status values
            $table->enum('status', ['active', 'suspended', 'trial'])
                  ->default('trial')
                  ->change();
        });

        Schema::table('billing_plans', function (Blueprint $table) {
            // Drop all new columns
            $columns = [
                // 'includes_financial_reports',
                'includes_expense_reports',
                'includes_order_reports',
                'includes_product_reports',
                'includes_inventory_reports',
                'includes_purchasing_reports',
                'includes_production_reports',
                'includes_restaurant_reports',
                'includes_sales_reports',
                'includes_customer_reports',
                'includes_supplier_reports',
                'includes_production_orders',
                'includes_bill_of_materials',
                'includes_work_orders',
                'includes_restaurant_management',
                'includes_table_management',
                'includes_menu_management',
                'includes_kitchen_display',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('billing_plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};