<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\Tenant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;
    
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['physical', 'digital', 'service', 'composite'];
        $name = $this->faker->unique()->words(2, true);

        return [
            'tenant_id' => Tenant::inRandomOrder()->first()->id,
            'category_id' => $this->faker->randomElement([2, 1, 4, 3, 9, 6, 5]),
            'sku' => $this->faker->unique()->bothify('SKU-####'),
            'name' => $this->faker->words(3, true),
            'created_by' => User::where('role_id', '1' )->where('status', 'active')->get()->random()->id,
            'slug' => Str::slug($name),
            'image_url' => $this->faker->imageUrl(640, 480, 'products', true),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement($types),
            'is_taxable' => $this->faker->boolean(80),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
