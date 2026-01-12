<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\PromotionProduct;
use App\Models\Promotion;
use App\Models\Product;
use App\Models\ProductVariant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PromotionProduct>
 */
class PromotionProductFactory extends Factory
{
    protected $model = PromotionProduct::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'promotion_id' => Promotion::inRandomOrder()->first()->id,
            'product_id' => Product::inRandomOrder()->first()->id,
            'variant_id' => ProductVariant::inRandomOrder()->first()->id,
            'applies_to' => $this->faker->randomElement(['specific_products', 'product_categories', 'all_products']),
        ];
    }
}
