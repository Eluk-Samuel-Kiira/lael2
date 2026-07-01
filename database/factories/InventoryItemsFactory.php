<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\ProductVariant;
use App\Models\Department;
use App\Models\Location;
use App\Models\Tenant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryItems>
 */
class InventoryItemsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get random active records - no DB check here, that's handled in the seeder
        $variant = ProductVariant::where('is_active', true)
            ->inRandomOrder()
            ->first();

        $department = Department::where('isActive', 1)
            ->inRandomOrder()
            ->first();

        $location = Location::where('is_active', 1)
            ->inRandomOrder()
            ->first();

        return [
            'quantity_on_hand' => $this->faker->randomElement([10, 22, 41, 3, 52, 45, 14, 35]),
            'quantity_allocated' => $this->faker->randomElement([10, 22, 41, 3, 52, 45, 14, 35]),
            'quantity_on_order' => $this->faker->randomElement([10, 22, 41, 3, 52, 45, 14, 35]),
            'reorder_point' => $this->faker->randomElement([1, 2, 4, 3, 5]),
            'preferred_stock_level' => $this->faker->randomElement([1, 2, 4, 3, 5]),
            'batch_number' => strtoupper($this->faker->unique()->bothify('BTH-####')),
            'expiry_date' => $this->faker->dateTimeBetween('-1 year', '+1 year')->format('Y-m-d'),
            'variant_id' => $variant->id,
            'department_id' => $department->id,
            'location_id' => $location->id,
            'tenant_id' => Tenant::inRandomOrder()->first()->id,
            'created_by' => User::where('role_id', '1')->where('status', 'active')->get()->random()->id,
        ];
    }
}