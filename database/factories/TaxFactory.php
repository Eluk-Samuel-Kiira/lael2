<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Tax;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tax>
 */
class TaxFactory extends Factory
{
    protected $model = Tax::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::inRandomOrder()->first()->id,
            'name' => $this->faker->randomElement(['VAT', 'Excise Duty', 'Service Tax']),
            'code' => strtoupper($this->faker->unique()->bothify('TAX###')),
            'rate' => $this->faker->randomElement([0.05, 0.10, 0.18, 0.20]),
            'type' => $this->faker->randomElement(['percentage', 'fixed']),
            'is_active' => true,
            'created_by' => User::inRandomOrder()->first()->id,
        ];
    }
}
