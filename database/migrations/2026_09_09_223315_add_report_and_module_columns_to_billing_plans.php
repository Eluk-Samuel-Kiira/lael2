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
        Schema::table('billing_plans', function (Blueprint $table) {
            // 🔥 Check if columns don't exist before adding
            
            // ============================================
            // REPORTS MODULES
            // ============================================
            if (!Schema::hasColumn('billing_plans', 'includes_expense_reports')) {
                $table->boolean('includes_expense_reports')->default(false)
                    ->after('includes_financial_reports')
                    ->comment('Enable expense reports module');
            }
            
            if (!Schema::hasColumn('billing_plans', 'includes_order_reports')) {
                $table->boolean('includes_order_reports')->default(false)
                    ->after('includes_expense_reports')
                    ->comment('Enable order reports module');
            }
            
            if (!Schema::hasColumn('billing_plans', 'includes_product_reports')) {
                $table->boolean('includes_product_reports')->default(false)
                    ->after('includes_order_reports')
                    ->comment('Enable product reports module');
            }
            
            if (!Schema::hasColumn('billing_plans', 'includes_inventory_reports')) {
                $table->boolean('includes_inventory_reports')->default(false)
                    ->after('includes_product_reports')
                    ->comment('Enable inventory reports module');
            }
            
            if (!Schema::hasColumn('billing_plans', 'includes_purchasing_reports')) {
                $table->boolean('includes_purchasing_reports')->default(false)
                    ->after('includes_inventory_reports')
                    ->comment('Enable purchasing reports module');
            }
            
            if (!Schema::hasColumn('billing_plans', 'includes_production_reports')) {
                $table->boolean('includes_production_reports')->default(false)
                    ->after('includes_purchasing_reports')
                    ->comment('Enable production reports module');
            }
            
            if (!Schema::hasColumn('billing_plans', 'includes_restaurant_reports')) {
                $table->boolean('includes_restaurant_reports')->default(false)
                    ->after('includes_production_reports')
                    ->comment('Enable restaurant reports module');
            }
            
            if (!Schema::hasColumn('billing_plans', 'includes_sales_reports')) {
                $table->boolean('includes_sales_reports')->default(false)
                    ->after('includes_restaurant_reports')
                    ->comment('Enable sales reports module');
            }
            
            if (!Schema::hasColumn('billing_plans', 'includes_customer_reports')) {
                $table->boolean('includes_customer_reports')->default(false)
                    ->after('includes_sales_reports')
                    ->comment('Enable customer reports module');
            }
            
            if (!Schema::hasColumn('billing_plans', 'includes_supplier_reports')) {
                $table->boolean('includes_supplier_reports')->default(false)
                    ->after('includes_customer_reports')
                    ->comment('Enable supplier reports module');
            }
            
            // ============================================
            // PRODUCTION MODULES
            // ============================================
            if (!Schema::hasColumn('billing_plans', 'includes_production_orders')) {
                $table->boolean('includes_production_orders')->default(false)
                    ->after('includes_supplier_reports')
                    ->comment('Enable production orders module');
            }
            
            if (!Schema::hasColumn('billing_plans', 'includes_bill_of_materials')) {
                $table->boolean('includes_bill_of_materials')->default(false)
                    ->after('includes_production_orders')
                    ->comment('Enable bill of materials module');
            }
            
            if (!Schema::hasColumn('billing_plans', 'includes_work_orders')) {
                $table->boolean('includes_work_orders')->default(false)
                    ->after('includes_bill_of_materials')
                    ->comment('Enable work orders module');
            }
            
            // ============================================
            // RESTAURANT MODULES
            // ============================================
            if (!Schema::hasColumn('billing_plans', 'includes_restaurant_management')) {
                $table->boolean('includes_restaurant_management')->default(false)
                    ->after('includes_work_orders')
                    ->comment('Enable restaurant management module');
            }
            
            if (!Schema::hasColumn('billing_plans', 'includes_table_management')) {
                $table->boolean('includes_table_management')->default(false)
                    ->after('includes_restaurant_management')
                    ->comment('Enable table management module');
            }
            
            if (!Schema::hasColumn('billing_plans', 'includes_menu_management')) {
                $table->boolean('includes_menu_management')->default(false)
                    ->after('includes_table_management')
                    ->comment('Enable menu management module');
            }
            
            if (!Schema::hasColumn('billing_plans', 'includes_kitchen_display')) {
                $table->boolean('includes_kitchen_display')->default(false)
                    ->after('includes_menu_management')
                    ->comment('Enable kitchen display module');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_plans', function (Blueprint $table) {
            // Drop all columns
            $columns = [
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