<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductVariant;
use App\Models\Product;

class ProductVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command->warn('No products found. Seeding sample products first...');
            $products = Product::factory(5)->create();
        }

        foreach ($products as $product) {
            ProductVariant::factory(4)->create([
                'product_id' => $product->id,
            ]);
        }

        // $this->command->info('Product variants seeded successfully.');
    }
}
