<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\TenantConfiguration; 
use App\Models\Tenant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TenantConfiguration>
 */
class TenantConfigurationFactory extends Factory
{
    protected $model = TenantConfiguration::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::inRandomOrder()->first()->id, // ✅ Now Tenant::inRandomOrder()->first()->id works
            'currency_code' => $this->faker->currencyCode,
            'timezone' => $this->faker->timezone,
            'locale' => $this->faker->locale,
            'fiscal_year_start' => $this->faker->date('Y-01-01'),
            'tax_calculation_method' => $this->faker->randomElement(['inclusive', 'exclusive']),
        ];
    }
}
