<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Location;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Department;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'first_name' => fake()->word(),
            'last_name' => fake()->word(),
            'telephone_number' => '0700000001',
            'job_title' => $this->faker->randomElement(['waiter', 'cleaner', 'atendant', 'keeper']),
            'department_id' => $this->faker->randomElement([1, 3, 4, 6, 7, 8, 9, 10]),
            'location_id' => $this->faker->randomElement([1, 3, 4, 6, 7, 8, 9, 10]),
            'profile_image' => '',
            'role_id' => $this->faker->randomElement([1, 3, 4]),
            'tenant_id' => Tenant::inRandomOrder()->first()->id,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
