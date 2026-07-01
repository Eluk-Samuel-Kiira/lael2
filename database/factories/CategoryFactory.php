<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Tenant;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);
        return [
            'tenant_id' => Tenant::inRandomOrder()->first()->id,
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'created_by' => User::where('role_id', '1' )->where('status', 'active')->get()->random()->id,
            'is_active' => $this->faker->boolean(90),
            'tenant_id' => $this->faker->randomElement([1, 2, 3, 4]),
        ];
    }
}
