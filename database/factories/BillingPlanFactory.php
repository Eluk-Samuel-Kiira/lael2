<?php
// database/factories/BillingPlanFactory.php

namespace Database\Factories;

use App\Models\BillingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class BillingPlanFactory extends Factory
{
    protected $model = BillingPlan::class;

    public function definition()
    {
        $planTypes = ['starter', 'professional', 'enterprise', 'custom', 'onetime'];
        $planType = $this->faker->randomElement($planTypes);
        
        $monthlyPrice = $planType === 'onetime' ? 0 : $this->faker->randomFloat(2, 9, 299);
        $annualPrice = $planType === 'onetime' ? 0 : $monthlyPrice * 12 * 0.8; // 20% discount for annual
        
        return [
            'plan_code' => $planType . '_' . $this->faker->unique()->word(),
            'plan_name' => ucfirst($planType) . ' Plan',
            'description' => $this->faker->paragraph(),
            'monthly_price' => $monthlyPrice,
            'annual_price' => $annualPrice,
            'onetime_fee' => $planType === 'onetime' ? $this->faker->randomFloat(2, 99, 999) : 0,
            'setup_fee' => $this->faker->randomFloat(2, 0, 199),
            'currency' => 'USD',
            'default_shops' => $this->faker->numberBetween(1, 10),
            'default_locations' => $this->faker->numberBetween(1, 5),
            'default_departments' => $this->faker->numberBetween(3, 20),
            'default_users' => $this->faker->numberBetween(1, 50),
            'default_products' => $this->faker->numberBetween(100, 10000),
            'default_customers' => $this->faker->numberBetween(100, 5000),
            'default_suppliers' => $this->faker->numberBetween(50, 1000),
            'default_storage_gb' => $this->faker->numberBetween(1, 100),
            'includes_inventory' => $planType !== 'starter',
            'includes_accounting' => $planType !== 'starter',
            'includes_hr_payroll' => $planType === 'enterprise',
            'includes_multicurrency' => $planType === 'enterprise',
            'includes_advanced_reports' => $planType !== 'starter',
            'includes_api_access' => $planType !== 'starter',
            'includes_ecommerce' => $planType === 'enterprise' || $planType === 'professional',
            'includes_pos' => $planType === 'enterprise' || $planType === 'professional',
            'includes_crm' => $planType === 'enterprise',
            'includes_support_priority' => $planType === 'enterprise',
            'includes_custom_branding' => $planType === 'enterprise',
            'trial_days' => $this->faker->numberBetween(0, 30),
            'billing_cycle_days' => 30,
            'is_public' => $this->faker->boolean(80),
            'is_active' => $this->faker->boolean(90),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'features_list' => json_encode($this->generateFeatures($planType)),
            'limitations' => json_encode($this->generateLimitations($planType)),
        ];
    }

    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
                'is_public' => true,
            ];
        });
    }

    public function free()
    {
        return $this->state(function (array $attributes) {
            return [
                'plan_code' => 'free',
                'plan_name' => 'Free Plan',
                'monthly_price' => 0,
                'annual_price' => 0,
                'onetime_fee' => 0,
                'setup_fee' => 0,
                'default_users' => 1,
                'default_products' => 50,
                'default_storage_gb' => 0.5,
                'trial_days' => 0,
                'is_public' => true,
                'is_active' => true,
            ];
        });
    }

    public function starter()
    {
        return $this->state(function (array $attributes) {
            return [
                'plan_code' => 'starter',
                'plan_name' => 'Starter Plan',
                'monthly_price' => 19.99,
                'annual_price' => 199.99,
                'onetime_fee' => 0,
                'setup_fee' => 49.99,
                'default_users' => 3,
                'default_products' => 500,
                'default_storage_gb' => 5,
                'includes_inventory' => true,
                'includes_accounting' => true,
                'includes_advanced_reports' => true,
                'is_public' => true,
                'is_active' => true,
                'sort_order' => 1,
            ];
        });
    }

    public function professional()
    {
        return $this->state(function (array $attributes) {
            return [
                'plan_code' => 'professional',
                'plan_name' => 'Professional Plan',
                'monthly_price' => 49.99,
                'annual_price' => 499.99,
                'onetime_fee' => 0,
                'setup_fee' => 99.99,
                'default_users' => 10,
                'default_products' => 5000,
                'default_storage_gb' => 25,
                'includes_inventory' => true,
                'includes_accounting' => true,
                'includes_advanced_reports' => true,
                'includes_api_access' => true,
                'includes_ecommerce' => true,
                'includes_pos' => true,
                'is_public' => true,
                'is_active' => true,
                'sort_order' => 2,
            ];
        });
    }

    public function enterprise()
    {
        return $this->state(function (array $attributes) {
            return [
                'plan_code' => 'enterprise',
                'plan_name' => 'Enterprise Plan',
                'monthly_price' => 199.99,
                'annual_price' => 1999.99,
                'onetime_fee' => 0,
                'setup_fee' => 299.99,
                'default_users' => 50,
                'default_products' => 50000,
                'default_storage_gb' => 100,
                'includes_inventory' => true,
                'includes_accounting' => true,
                'includes_hr_payroll' => true,
                'includes_multicurrency' => true,
                'includes_advanced_reports' => true,
                'includes_api_access' => true,
                'includes_ecommerce' => true,
                'includes_pos' => true,
                'includes_crm' => true,
                'includes_support_priority' => true,
                'includes_custom_branding' => true,
                'is_public' => true,
                'is_active' => true,
                'sort_order' => 3,
            ];
        });
    }

    public function onetime()
    {
        return $this->state(function (array $attributes) {
            return [
                'plan_code' => 'onetime_' . $this->faker->unique()->word(),
                'plan_name' => 'One-Time Purchase',
                'monthly_price' => 0,
                'annual_price' => 0,
                'onetime_fee' => $this->faker->randomFloat(2, 299, 2999),
                'setup_fee' => 0,
                'default_users' => 5,
                'default_products' => 1000,
                'default_storage_gb' => 10,
                'includes_inventory' => true,
                'includes_accounting' => true,
                'trial_days' => 0,
                'billing_cycle_days' => 0,
                'is_public' => true,
                'is_active' => true,
                'sort_order' => 4,
            ];
        });
    }

    private function generateFeatures(string $planType): array
    {
        $features = [
            'Basic Inventory Tracking',
            'Sales & Purchase Management',
            'Customer & Supplier Management',
            'Basic Reports',
            'Email Support',
        ];

        if ($planType !== 'starter') {
            $features = array_merge($features, [
                'Advanced Reporting',
                'API Access',
                'Custom Fields',
                'Bulk Operations',
                'Priority Email Support',
            ]);
        }

        if ($planType === 'enterprise') {
            $features = array_merge($features, [
                'Multi-currency Support',
                'HR & Payroll Integration',
                'CRM Integration',
                'Custom Branding',
                'Dedicated Support',
                'Custom Development',
                'Training Sessions',
            ]);
        }

        if ($planType === 'onetime') {
            $features = array_merge($features, [
                'Lifetime License',
                'One-Time Payment',
                'Perpetual Updates (1 year)',
                'No Recurring Fees',
            ]);
        }

        return $features;
    }

    private function generateLimitations(string $planType): array
    {
        $limitations = [];

        if ($planType === 'starter') {
            $limitations = [
                'Limited to 3 users',
                '500 products maximum',
                '5GB storage',
                'No API access',
                'Basic support only',
                'No multi-currency',
            ];
        } elseif ($planType === 'professional') {
            $limitations = [
                'Limited to 10 users',
                '5000 products maximum',
                '25GB storage',
                'No HR/payroll features',
                'No custom branding',
            ];
        } elseif ($planType === 'enterprise') {
            $limitations = [
                'Custom limits apply',
                'Annual billing required',
                'Contract commitment',
            ];
        } elseif ($planType === 'onetime') {
            $limitations = [
                'No recurring updates after 1 year',
                'Limited to major version',
                'Support limited to 1 year',
                'No cloud backup',
            ];
        }

        return $limitations;
    }
}