<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Location;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array 
    {
        return [
            'name' => fake()->word() .' Department',
            'isActive' => 1,
            'tenant_id' => Tenant::inRandomOrder()->first()->id,
            'created_by' => User::where('role_id', 1)->where('status', 'active')->get()->random()->id,
            'manager_id' => User::where('role_id', 1)->where('status', 'active')->get()->random()->id,
            'location_id' => Location::where('is_active', true)->get()->random()->id,
        ];
    
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['isActive' => 0]);
    }
    
}
