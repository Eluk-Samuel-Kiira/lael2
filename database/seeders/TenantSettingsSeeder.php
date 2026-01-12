<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantSettingsSeeder extends Seeder
{
    /**
     * Default settings configuration
     */
    protected array $defaultSettings = [
        // POS Shop & Location Limits
        'limits' => [
            'max_pos_shops' => ['value' => 1, 'type' => 'integer'],
            'max_locations' => ['value' => 1, 'type' => 'integer'],
            'max_departments' => ['value' => 3, 'type' => 'integer'],
            'max_users' => ['value' => 3, 'type' => 'integer'],
            'max_products' => ['value' => 1000, 'type' => 'integer'],
            'max_customers' => ['value' => 5000, 'type' => 'integer'],
            'max_employees' => ['value' => 10, 'type' => 'integer'],
            'max_terminals_per_shop' => ['value' => 2, 'type' => 'integer'],
            'max_api_keys' => ['value' => 2, 'type' => 'integer'],
            'max_webhooks' => ['value' => 5, 'type' => 'integer'],
            'max_integrations' => ['value' => 3, 'type' => 'integer'],
            'data_retention_months' => ['value' => 24, 'type' => 'integer'],
        ],

        // Feature Flags & Module Access
        'features' => [
            'module_inventory' => ['value' => true, 'type' => 'boolean'],
            'module_accounting' => ['value' => true, 'type' => 'boolean'],
            'module_hr_payroll' => ['value' => false, 'type' => 'boolean'],
            'module_multicurrency' => ['value' => false, 'type' => 'boolean'],
            'module_loyalty' => ['value' => false, 'type' => 'boolean'],
            'module_advanced_reports' => ['value' => false, 'type' => 'boolean'],
            'allow_data_export' => ['value' => false, 'type' => 'boolean'],
            'auto_backup_enabled' => ['value' => true, 'type' => 'boolean'],
        ],

        // Billing & Subscription
        'billing' => [
            'billing_plan' => ['value' => 'starter', 'type' => 'string'],
            'subscription_status' => ['value' => 'active', 'type' => 'string'],
            'trial_ends_at' => ['value' => null, 'type' => 'datetime'],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all tenants to apply default settings
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $settings = [];
            
            foreach ($this->defaultSettings as $category => $categorySettings) {
                foreach ($categorySettings as $key => $config) {
                    $settings[] = [
                        'tenant_id' => $tenant->id,
                        'setting_key' => $key,
                        'setting_value' => $config['value'],
                        'data_type' => $config['type'],
                        'category' => $category,
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
}