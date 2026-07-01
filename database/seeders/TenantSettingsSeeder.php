<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TenantSettingsSeeder extends Seeder
{
    /**
     * Lifetime plan settings configuration
     * Based on the lifetime plan from BillingPlanFactory
     */
    protected array $lifetimeSettings = [
        // POS Shop & Location Limits - Unlimited (using 999999 as max)
        'limits' => [
            'max_pos_shops' => ['value' => 999999, 'type' => 'integer'],
            'max_locations' => ['value' => 999999, 'type' => 'integer'],
            'max_departments' => ['value' => 999999, 'type' => 'integer'],
            'max_users' => ['value' => 999999, 'type' => 'integer'],
            'max_products' => ['value' => 999999, 'type' => 'integer'],
            'max_customers' => ['value' => 999999, 'type' => 'integer'],
            'max_suppliers' => ['value' => 999999, 'type' => 'integer'],
            'max_employees' => ['value' => 999999, 'type' => 'integer'],
            'max_payment_methods' => ['value' => 999999, 'type' => 'integer'],
            'max_terminals_per_shop' => ['value' => 999999, 'type' => 'integer'],
            'max_api_keys' => ['value' => 999999, 'type' => 'integer'],
            'max_webhooks' => ['value' => 999999, 'type' => 'integer'],
            'max_integrations' => ['value' => 999999, 'type' => 'integer'],
            'storage_gb' => ['value' => 999999, 'type' => 'integer'],
            'data_retention_months' => ['value' => 120, 'type' => 'integer'], // 10 years
        ],

        // Feature Flags & Module Access - All Enabled for Lifetime Plan
        'features' => [
            // Core Retail Features
            'includes_dashboard' => ['value' => true, 'type' => 'boolean'],
            'includes_pos' => ['value' => true, 'type' => 'boolean'],
            'includes_product_catalog' => ['value' => true, 'type' => 'boolean'],
            'includes_procurement' => ['value' => true, 'type' => 'boolean'],
            'includes_settings' => ['value' => true, 'type' => 'boolean'],
            'includes_users' => ['value' => true, 'type' => 'boolean'],
            
            // Inventory & Expenses
            'module_inventory' => ['value' => true, 'type' => 'boolean'],
            'includes_inventory' => ['value' => true, 'type' => 'boolean'],
            'includes_expenses' => ['value' => true, 'type' => 'boolean'],
            
            // Accounting & Finance
            'module_accounting' => ['value' => true, 'type' => 'boolean'],
            'includes_accounting' => ['value' => true, 'type' => 'boolean'],
            'includes_advanced_accounting' => ['value' => true, 'type' => 'boolean'],
            'includes_financial_reports' => ['value' => true, 'type' => 'boolean'],
            
            // HR & Payroll
            'module_hr_payroll' => ['value' => true, 'type' => 'boolean'],
            'includes_hr_payroll' => ['value' => true, 'type' => 'boolean'],
            
            // Multi-currency & Advanced
            'module_multicurrency' => ['value' => true, 'type' => 'boolean'],
            'includes_multicurrency' => ['value' => true, 'type' => 'boolean'],
            'module_advanced_reports' => ['value' => true, 'type' => 'boolean'],
            'includes_advanced_reports' => ['value' => true, 'type' => 'boolean'],
            
            // E-commerce & Integrations
            'includes_ecommerce' => ['value' => true, 'type' => 'boolean'],
            'includes_api_access' => ['value' => true, 'type' => 'boolean'], // Still false as per factory
            'includes_crm' => ['value' => true, 'type' => 'boolean'], // Still false as per factory
            
            // Support & Branding
            'includes_support_priority' => ['value' => true, 'type' => 'boolean'],
            'includes_custom_branding' => ['value' => true, 'type' => 'boolean'],
            
            // Loyalty & Data
            'module_loyalty' => ['value' => true, 'type' => 'boolean'],
            'allow_data_export' => ['value' => true, 'type' => 'boolean'],
            'auto_backup_enabled' => ['value' => true, 'type' => 'boolean'],
            
            // Hotel Features - All False for now (Future)
            'includes_front_desk' => ['value' => false, 'type' => 'boolean'],
            'includes_housekeeping' => ['value' => false, 'type' => 'boolean'],
            'includes_room_management' => ['value' => false, 'type' => 'boolean'],
            'includes_reservations' => ['value' => false, 'type' => 'boolean'],
            'includes_guest_management' => ['value' => false, 'type' => 'boolean'],
            'includes_group_booking' => ['value' => false, 'type' => 'boolean'],
            'includes_travel_agent' => ['value' => false, 'type' => 'boolean'],
            'includes_night_audit' => ['value' => false, 'type' => 'boolean'],
            'includes_guest_history' => ['value' => false, 'type' => 'boolean'],
            'includes_lost_found' => ['value' => false, 'type' => 'boolean'],
            'includes_guest_services' => ['value' => false, 'type' => 'boolean'],
            'includes_events_banqueting' => ['value' => false, 'type' => 'boolean'],
            'includes_event_booking' => ['value' => false, 'type' => 'boolean'],
            'includes_function_sheets' => ['value' => false, 'type' => 'boolean'],
            'includes_outside_catering' => ['value' => false, 'type' => 'boolean'],
            'includes_venue_management' => ['value' => false, 'type' => 'boolean'],
            'includes_equipment_management' => ['value' => false, 'type' => 'boolean'],
            'includes_asset_management' => ['value' => false, 'type' => 'boolean'],
            'includes_asset_register' => ['value' => false, 'type' => 'boolean'],
            'includes_maintenance_management' => ['value' => false, 'type' => 'boolean'],
            'includes_repair_jobs' => ['value' => false, 'type' => 'boolean'],
            'includes_maintenance_schedules' => ['value' => false, 'type' => 'boolean'],
            'includes_laundry_management' => ['value' => false, 'type' => 'boolean'],
            'includes_restaurant_management' => ['value' => false, 'type' => 'boolean'],
            'includes_hotel_management' => ['value' => false, 'type' => 'boolean'],
            'includes_booking_engine' => ['value' => false, 'type' => 'boolean'],
            'includes_channel_manager' => ['value' => false, 'type' => 'boolean'],
            'includes_phone_book' => ['value' => false, 'type' => 'boolean'],
            'includes_guest_preferences' => ['value' => false, 'type' => 'boolean'],
        ],

        // Billing & Subscription - Lifetime Plan
        'billing' => [
            'billing_plan' => ['value' => 'onetime_lifetime', 'type' => 'string'],
            'plan_name' => ['value' => 'Lifetime License', 'type' => 'string'],
            'subscription_status' => ['value' => 'active', 'type' => 'string'],
            'billing_cycle' => ['value' => 'one-time', 'type' => 'string'],
            'billing_cycle_days' => ['value' => null, 'type' => 'integer'],
            'trial_days' => ['value' => 0, 'type' => 'integer'],
            'trial_ends_at' => ['value' => null, 'type' => 'datetime'],
            'monthly_price' => ['value' => 0, 'type' => 'decimal'],
            'annual_price' => ['value' => 0, 'type' => 'decimal'],
            'onetime_fee' => ['value' => 999.99, 'type' => 'decimal'],
            'setup_fee' => ['value' => 0, 'type' => 'decimal'],
            'currency' => ['value' => 'USD', 'type' => 'string'],
            'is_lifetime' => ['value' => true, 'type' => 'boolean'],
            'lifetime_purchase_date' => ['value' => null, 'type' => 'datetime'],
            'next_billing_date' => ['value' => null, 'type' => 'datetime'],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all tenants to apply lifetime settings
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $settings = [];
            
            foreach ($this->lifetimeSettings as $category => $categorySettings) {
                foreach ($categorySettings as $key => $config) {
                    $value = $config['value'];
                    
                    // Special handling for lifetime_purchase_date if we want to set it
                    if ($key === 'lifetime_purchase_date' && $value === null) {
                        $value = now();
                    }
                    
                    $settings[] = [
                        'tenant_id' => $tenant->id,
                        'setting_key' => $key,
                        'setting_value' => $this->formatValueForDb($value, $config['type']),
                        'data_type' => $config['type'],
                        'category' => $category,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Insert in chunks for better performance
            foreach (array_chunk($settings, 50) as $chunk) {
                DB::table('tenant_settings')->insert($chunk);
            }
        }
    }

    /**
     * Format value based on type for database storage
     */
    protected function formatValueForDb($value, string $type): string
    {
        if ($value === null) {
            return '';
        }
        
        return match($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            'decimal' => (string) $value,
            'datetime' => $value instanceof \DateTime ? $value->format('Y-m-d H:i:s') : (string) $value,
            default => (string) $value,
        };
    }
}