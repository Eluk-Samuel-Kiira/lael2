<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ProductVariant;
use App\Models\Product;
use App\Models\User;
use App\Models\Tenant;

class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        // Generate float prices first, then convert to integers
        $priceFloat = $this->faker->randomFloat(2, 5, 500);
        $costPriceFloat = $this->faker->randomFloat(2, 3, 400);
        
        // Get a random product
        $product = Product::inRandomOrder()->first() ?? Product::factory()->create();
        
        // Get a random tenant
        $tenant = Tenant::inRandomOrder()->first() ?? Tenant::factory()->create();
        
        // Get a user for this tenant
        $user = User::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->inRandomOrder()
            ->first();
            
        if (!$user) {
            $user = User::factory()->create([
                'tenant_id' => $tenant->id,
                'role_id' => 1,
                'status' => 'active'
            ]);
        }

        return [
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####')),
            'name' => $this->faker->word(),
            'barcode' => $this->faker->ean13(),
            'price' => to_base_currency($priceFloat), // Convert to integer
            'product_id' => $product->id,
            'cost_price' => to_base_currency($costPriceFloat), // Convert to integer
            'image_url' => $this->faker->imageUrl(640, 480, 'products', true),
            'weight' => $this->faker->randomFloat(2, 50, 2000),
            'weight_unit' => $this->faker->randomElement([1, 2, 4, 3, 5]),
            'overal_quantity_at_hand' => $this->faker->randomElement([10, 22, 43, 35, 50, 300, 74, 100]),
            'is_active' => $this->faker->boolean(90),
            'is_taxable' => $this->faker->boolean(80),
            'tenant_id' => $tenant->id,
            'created_by' => $user->id,
        ];
    }
}