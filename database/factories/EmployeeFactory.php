<?php
// database/factories/EmployeeFactory.php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $user = User::inRandomOrder()->first() ?? User::factory()->create();
        
        return [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id ?? 1,
            'department_id' => $user->department_id ?? 1,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->telephone_number ?? $this->faker->phoneNumber(),
            'job_title' => $user->job_title ?? $this->faker->jobTitle(),
            'hire_date' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'termination_date' => $this->faker->optional(0.1)->dateTimeBetween('-1 year', 'now'),
            'salary' => $this->faker->randomFloat(2, 30000, 150000),
            'salary_type' => $this->faker->randomElement(['hourly', 'weekly', 'monthly', 'annual']),
            'is_active' => $this->faker->boolean(90),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'termination_date' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'termination_date' => now()->subDays(rand(1, 365)),
        ]);
    }
}