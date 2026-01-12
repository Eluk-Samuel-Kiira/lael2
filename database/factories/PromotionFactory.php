<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Promotion;
use App\Models\Tenant;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Promotion>
 */
class PromotionFactory extends Factory
{
    protected $model = Promotion::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::inRandomOrder()->first()->id,
            'name' => $this->faker->unique()->word,
            'description' => $this->faker->sentence,
            'discount_type' => $this->faker->randomElement(['percentage', 'fixed_amount', 'buy_x_get_y']),
            'discount_value' => $this->faker->randomFloat(2, 5, 50),
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'is_active' => true,
            'max_uses' => $this->faker->numberBetween(10, 100),
            'max_uses_per_customer' => $this->faker->numberBetween(1, 5),
            'min_order_amount' => $this->faker->randomFloat(2, 50, 500),
            'created_by' => User::inRandomOrder()->first()->id,
        ];
    }
}
