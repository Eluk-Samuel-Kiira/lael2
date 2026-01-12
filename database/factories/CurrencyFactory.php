<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Tenant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = \App\Models\Currency::class;
    
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::inRandomOrder()->first()->id,
            'code' => $this->faker->currencyCode(),
            'name' => $this->faker->word(),
            'symbol' => $this->faker->randomElement(['$', '€', '£', '¥']),
            'exchange_rate' => $this->faker->randomFloat(6, 0.5, 1.5),
            'created_by' => User::where('role_id', '1' )->where('status', 'active')->get()->random()->id,
            'isActive' => $this->faker->randomElement([0, 1]), // Randomly assigns 0 or 1
            'isBaseCurrency' => 0, 
        ];
    }
}
