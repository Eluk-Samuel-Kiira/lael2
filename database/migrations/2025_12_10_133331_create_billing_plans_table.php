<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_billing_plans_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('billing_plans', function (Blueprint $table) {
            $table->id('plan_id');
            $table->string('plan_code', 50)->unique();
            $table->string('plan_name', 100);
            $table->text('description')->nullable();
            
            // Pricing
            $table->decimal('monthly_price', 12, 2)->default(0);
            $table->decimal('annual_price', 12, 2)->default(0);
            $table->decimal('onetime_fee', 12, 2)->default(0);
            $table->decimal('setup_fee', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            
            // Limits
            $table->integer('default_shops')->default(1);
            $table->integer('default_payment_methods')->default(1);
            $table->integer('default_locations')->default(1);
            $table->integer('default_departments')->default(3);
            $table->integer('default_users')->default(1);
            $table->integer('default_products')->default(100);
            $table->integer('default_customers')->default(100);
            $table->integer('default_suppliers')->default(50);
            $table->integer('default_storage_gb')->default(1);
            $table->integer('default_rooms')->default(0); // For hotel
            $table->integer('default_events')->default(0); // For events
            
            // RETAIL/POS FEATURES (Current)
            $table->boolean('includes_dashboard')->default(false);
            $table->boolean('includes_pos')->default(false);
            $table->boolean('includes_product_catelog')->default(false);
            $table->boolean('includes_procurement')->default(false);
            $table->boolean('includes_settings')->default(false);
            $table->boolean('includes_users')->default(false);
            $table->boolean('includes_inventory')->default(false);
            $table->boolean('includes_expenses')->default(false);
            $table->boolean('includes_accounting')->default(false);
            $table->boolean('includes_advanced_accounting')->default(false);
            $table->boolean('includes_hr_payroll')->default(false);
            $table->boolean('includes_multicurrency')->default(false);
            $table->boolean('includes_financial_reports')->default(false);
            $table->boolean('includes_advanced_reports')->default(false);
            $table->boolean('includes_api_access')->default(false);
            $table->boolean('includes_ecommerce')->default(false);
            $table->boolean('includes_crm')->default(false);
            $table->boolean('includes_support_priority')->default(false);
            $table->boolean('includes_custom_branding')->default(false);
            
            // FRONT DESK / RECEPTION MODULES (Future Hotel)
            $table->boolean('includes_front_desk')->default(false);
            $table->boolean('includes_housekeeping')->default(false);
            $table->boolean('includes_room_management')->default(false);
            $table->boolean('includes_reservations')->default(false);
            $table->boolean('includes_guest_management')->default(false);
            $table->boolean('includes_group_booking')->default(false);
            $table->boolean('includes_travel_agent')->default(false);
            $table->boolean('includes_night_audit')->default(false);
            $table->boolean('includes_guest_history')->default(false);
            $table->boolean('includes_lost_found')->default(false);
            $table->boolean('includes_guest_services')->default(false);
            $table->boolean('includes_phone_book')->default(false);
            $table->boolean('includes_guest_preferences')->default(false);
            
            // EVENTS & BANQUETING MODULES (Future Hotel)
            $table->boolean('includes_events_banqueting')->default(false);
            $table->boolean('includes_event_booking')->default(false);
            $table->boolean('includes_function_sheets')->default(false);
            $table->boolean('includes_outside_catering')->default(false);
            $table->boolean('includes_venue_management')->default(false);
            $table->boolean('includes_equipment_management')->default(false);
            
            // ASSETS MANAGEMENT MODULES
            $table->boolean('includes_asset_management')->default(false);
            $table->boolean('includes_asset_register')->default(false);
            
            // MAINTENANCE & REPAIRS MODULES
            $table->boolean('includes_maintenance_management')->default(false);
            $table->boolean('includes_repair_jobs')->default(false);
            $table->boolean('includes_maintenance_schedules')->default(false);
            
            // ADDITIONAL HOTEL FEATURES
            $table->boolean('includes_laundry_management')->default(false);
            $table->boolean('includes_restaurant_management')->default(false);
            $table->boolean('includes_hotel_management')->default(false);
            $table->boolean('includes_booking_engine')->default(false);
            $table->boolean('includes_channel_manager')->default(false);
            
            // Trial & Billing
            $table->integer('trial_days')->default(14);
            $table->integer('billing_cycle_days')->default(30);
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            // Metadata
            $table->json('features_list')->nullable();
            $table->json('limitations')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['plan_code', 'is_active']);
            $table->index(['is_public', 'is_active', 'sort_order']);
            $table->index('monthly_price');
        });
    }

    public function down()
    {
        Schema::dropIfExists('billing_plans');
    }
};