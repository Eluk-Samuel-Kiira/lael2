<?php

namespace Database\Factories;
use App\Models\Location;
use App\Models\Tenant;
use App\Models\User;

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
        return [
            'tenant_id' => Tenant::inRandomOrder()->first()->id,
            'name' => $this->faker->unique()->company(),
            'address' => $this->faker->address(),
            'is_primary' => $this->faker->boolean(20),
            'is_active' => true,
            'created_by' => User::where('role_id', '1' )->where('status', 'active')->get()->random()->id,
            'manager_id' => User::where('role_id', 1)->where('status', 'active')->get()->random()->id,
        ];
    }
}
