<?php
// database/factories/TenantUsageTrackingFactory.php

namespace Database\Factories;

use App\Models\TenantUsageTracking;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantUsageTrackingFactory extends Factory
{
    protected $model = TenantUsageTracking::class;

    public function definition()
    {
        $trackingDate = $this->faker->dateTimeBetween('-30 days', 'now');
        $tenant = Tenant::inRandomOrder()->first() ?? Tenant::factory()->create();
        
        $currentShops = $this->faker->numberBetween(1, 10);
        $currentUsers = $this->faker->numberBetween(1, 50);
        $currentProducts = $this->faker->numberBetween(100, 10000);
        
        return [
            'tenant_id' => $tenant->id,
            'tracking_date' => $trackingDate,
            'current_shops' => $currentShops,
            'current_locations' => $this->faker->numberBetween(1, $currentShops * 3),
            'current_departments' => $this->faker->numberBetween(1, 20),
            'current_users' => $currentUsers,
            'current_products' => $currentProducts,
            'current_customers' => $this->faker->numberBetween(50, 5000),
            'current_employees' => $this->faker->numberBetween(1, 100),
            'current_api_keys' => $this->faker->numberBetween(0, 10),
            'current_webhooks' => $this->faker->numberBetween(0, 20),
            'current_integrations' => $this->faker->numberBetween(0, 5),
            'monthly_sales_count' => $this->faker->numberBetween(100, 10000),
            'monthly_api_calls' => $this->faker->numberBetween(1000, 100000),
            'monthly_storage_mb' => $this->faker->randomFloat(2, 10, 10240),
            'average_response_time_ms' => $this->faker->randomFloat(2, 50, 500),
            'error_rate_percent' => $this->faker->randomFloat(2, 0.01, 5),
            'active_sessions' => $this->faker->numberBetween(0, $currentUsers),
            'concurrent_users' => $this->faker->numberBetween(0, min(50, $currentUsers)),
            'estimated_cost' => $this->faker->randomFloat(2, 10, 1000),
            'resource_utilization_percent' => $this->faker->randomFloat(2, 10, 95),
            'exceeds_plan_limits' => $this->faker->boolean(10), // 10% chance of exceeding limits
        ];
    }

    public function forTenant($tenantId)
    {
        return $this->state(function (array $attributes) use ($tenantId) {
            return [
                'tenant_id' => $tenantId,
            ];
        });
    }

    public function forDate($date)
    {
        return $this->state(function (array $attributes) use ($date) {
            return [
                'tracking_date' => $date,
            ];
        });
    }

    public function forToday()
    {
        return $this->state(function (array $attributes) {
            return [
                'tracking_date' => Carbon::today(),
            ];
        });
    }

    public function forYesterday()
    {
        return $this->state(function (array $attributes) {
            return [
                'tracking_date' => Carbon::yesterday(),
            ];
        });
    }

    public function withHighUsage()
    {
        return $this->state(function (array $attributes) {
            return [
                'current_users' => $this->faker->numberBetween(100, 500),
                'current_products' => $this->faker->numberBetween(10000, 50000),
                'monthly_storage_mb' => $this->faker->randomFloat(2, 5120, 20480),
                'monthly_api_calls' => $this->faker->numberBetween(100000, 1000000),
                'resource_utilization_percent' => $this->faker->randomFloat(2, 80, 100),
                'exceeds_plan_limits' => true,
            ];
        });
    }

    public function withLowUsage()
    {
        return $this->state(function (array $attributes) {
            return [
                'current_users' => $this->faker->numberBetween(1, 5),
                'current_products' => $this->faker->numberBetween(10, 100),
                'monthly_storage_mb' => $this->faker->randomFloat(2, 1, 100),
                'monthly_api_calls' => $this->faker->numberBetween(100, 1000),
                'resource_utilization_percent' => $this->faker->randomFloat(2, 1, 30),
                'exceeds_plan_limits' => false,
            ];
        });
    }

    public function exceedingLimits()
    {
        return $this->state(function (array $attributes) {
            return [
                'exceeds_plan_limits' => true,
            ];
        });
    }

    public function withinLimits()
    {
        return $this->state(function (array $attributes) {
            return [
                'exceeds_plan_limits' => false,
            ];
        });
    }
}