<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderTax>
 */
class OrderTaxFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rate = $this->faker->randomElement([5, 10, 15, 18]);
        $amount = $this->faker->randomFloat(2, 5, 200);

        return [
            'order_id' => Order::inRandomOrder()->first()->id,
            'tax_name' => $this->faker->randomElement(['VAT', 'GST', 'Sales Tax']),
            'tax_rate' => $rate,
            'tax_amount' => $amount,
            'is_compound' => $this->faker->boolean(20), // 20% chance
            'created_by' => User::query()->inRandomOrder()->value('id'),
        ];
    }
}
