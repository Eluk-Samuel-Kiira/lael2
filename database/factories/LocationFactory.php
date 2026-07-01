<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tenantId = Tenant::inRandomOrder()->first()->id;
        
        // Get a random currency for this tenant or use USD as fallback
        $currency = Currency::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->inRandomOrder()
            ->first();
        
        return [
            'tenant_id' => $tenantId,
            'name' => $this->faker->unique()->company() . ' ' . $this->faker->city() . ' Store',
            'address' => $this->faker->address(),
            'is_primary' => false, // Will be set conditionally in seeder
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'created_by' => User::where('role_id', 1)->where('status', 'active')->inRandomOrder()->first()->id ?? 1,
            'manager_id' => User::where('role_id', 1)->where('status', 'active')->inRandomOrder()->first()->id ?? 1,
            'currency_id' => $currency ? $currency->id : null,
        ];
    }

    /**
     * Indicate that the location is primary.
     */
    public function primary()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_primary' => true,
            ];
        });
    }

    /**
     * Indicate that the location is inactive.
     */
    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    /**
     * Set a specific currency for the location.
     */
    public function withCurrency($currencyId)
    {
        return $this->state(function (array $attributes) use ($currencyId) {
            return [
                'currency_id' => $currencyId,
            ];
        });
    }

    /**
     * Set a specific manager for the location.
     */
    public function withManager($managerId)
    {
        return $this->state(function (array $attributes) use ($managerId) {
            return [
                'manager_id' => $managerId,
            ];
        });
    }

    /**
     * For a specific tenant.
     */
    public function forTenant($tenantId)
    {
        return $this->state(function (array $attributes) use ($tenantId) {
            return [
                'tenant_id' => $tenantId,
            ];
        });
    }
}