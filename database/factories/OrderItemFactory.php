<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = $this->faker->randomFloat(2, 10, 200);
        $quantity = $this->faker->numberBetween(1, 5);
        $discount = $this->faker->randomFloat(2, 0, 20);
        $taxAmount = $this->faker->randomFloat(2, 0, 15);
        
        return [
            'order_id' => Order::inRandomOrder()->first()->id,
            'product_id' => Product::inRandomOrder()->first()->id,
            'variant_id' => ProductVariant::inRandomOrder()->first()->id,
            'item_name' => $this->faker->words(3, true),
            'sku' => $this->faker->unique()->bothify('SKU#####'),
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'discount' => $discount,
            'tax_amount' => $taxAmount,
            'total_price' => ($unitPrice * $quantity) - $discount + $taxAmount,
            'inventory_data' => [
                'initial_stock' => $this->faker->numberBetween(50, 200),
                'current_stock' => $this->faker->numberBetween(10, 180),
                'cost_price' => $this->faker->randomFloat(2, 5, 100),
                'restock_level' => $this->faker->numberBetween(10, 30),
                'last_restocked' => $this->faker->dateTimeThisYear()->format('Y-m-d H:i:s')
            ]
        ];
    }
}
