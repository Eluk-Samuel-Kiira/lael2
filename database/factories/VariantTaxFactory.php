<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use App\Models\VariantTax;
use App\Models\ProductVariant;
use App\Models\Tax;
use App\Models\User;
use App\Models\Tenant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VariantTax>
 */
class VariantTaxFactory extends Factory
{
    protected $model = VariantTax::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    public function definition(): array
    {
        return [
            'variant_id' => ProductVariant::inRandomOrder()->first()->id,
            'tax_id' => Tax::inRandomOrder()->first()->id,
            'created_by' => User::inRandomOrder()->first()->id,
            'tenant_id' => Tenant::inRandomOrder()->first()->id,
        ];
    }
}
