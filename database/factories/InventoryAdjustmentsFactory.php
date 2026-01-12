<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\InventoryItems;
use App\Models\Tenant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryAdjustments>
 */
class InventoryAdjustmentsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quantity_before' => $this->faker->randomElement([10, 22, 41, 3, 52, 45, 14, 35]),
            'quantity_after' => $this->faker->randomElement([10, 22, 41, 3, 52, 45, 14, 35]),
            'reason' => $this->faker->sentence(),
            'notes' => $this->faker->sentence(),
            'inventory_id' => InventoryItems::inRandomOrder()->value('id'),
            'tenant_id' => Tenant::inRandomOrder()->first()->id,
            'created_by' => User::where('role_id', '1' )->where('status', 'active')->get()->random()->id,
        ];
    }
}
