<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\InventoryItems;
use App\Models\Tenant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryTransactions>
 */
class InventoryTransactionsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['purchase', 'sale', 'return', 'adjustment', 'transfer_in', 'transfer_out'];

        return [
            'quantity' => $this->faker->randomElement([10, 22, 41, 3, 52, 45, 14, 35]),
            'reference_id' => $this->faker->randomElement([10, 22, 41, 3, 52, 45, 14, 35]),
            'reference_type' => $this->faker->unique()->words(2, true),
            'type' => $this->faker->randomElement($types),
            'inventory_id' => InventoryItems::inRandomOrder()->value('id'),
            'notes' => $this->faker->sentence(),
            'tenant_id' => Tenant::inRandomOrder()->first()->id,
            'created_by' => User::where('role_id', '1' )->where('status', 'active')->get()->random()->id,
        ];
    }
}
