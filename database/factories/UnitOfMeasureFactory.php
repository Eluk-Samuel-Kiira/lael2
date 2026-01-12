<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Tenant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UnitOfMeasure>
 */
class UnitOfMeasureFactory extends Factory
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
            'name' => $this->faker->word, 
            'symbol' => $this->faker->randomElement(['g', 'l', 'kg', 'ml']),
            'created_by' => User::where('role_id', '1' )->where('status', 'active')->get()->random()->id,
            'isActive' => $this->faker->randomElement([0, 1]), // Randomly assigns 0 or 1
        ];
    }
}
