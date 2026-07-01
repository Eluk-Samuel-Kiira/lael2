<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\Tenant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);
        
        return [
            'tenant_id' => Tenant::inRandomOrder()->first()->id,
            'parent_category_id' => $this->faker->randomElement([2, 1, 4, 3]), // Can assign later for subcategories
            'name' => $this->faker->unique()->words(2, true),
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'image_url' => $this->faker->imageUrl(640, 480, 'products', true),
            'created_by' => User::where('role_id', '1' )->where('status', 'active')->get()->random()->id,
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
