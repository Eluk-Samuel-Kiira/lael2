<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Tenant;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerGroup>
 */
class CustomerGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::inRandomOrder()->first()->id,
            'name' => $this->faker->unique()->word(),
            'discount_percentage' => $this->faker->randomFloat(2, 0, 30),
            'created_by' => User::where('role_id', '1' )->where('status', 'active')->get()->random()->id,
            'is_default' => false,
        ];
    }
}
