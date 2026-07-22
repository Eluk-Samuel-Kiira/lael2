<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ProductVariant;
use App\Models\Product;
use App\Models\User;
use App\Models\Tenant;
use App\Models\UnitOfMeasure;

class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        // Generate float prices first, then convert to integers
        $supplierCostFloat = $this->faker->randomFloat(2, 3, 400);
        $shippingCostFloat = $this->faker->randomFloat(2, 1, 50);
        $taxesFloat = $this->faker->randomFloat(2, 0.5, 30);
        $expensesFloat = $this->faker->randomFloat(2, 0, 20);
        
        // Calculate grand total cost
        $grandTotalCostFloat = $supplierCostFloat + $shippingCostFloat + $taxesFloat + $expensesFloat;
        
        // Selling price with markup (50% - 200% markup)
        $markupPercentage = $this->faker->randomFloat(2, 50, 200);
        $sellingPriceFloat = $grandTotalCostFloat * (1 + ($markupPercentage / 100));
        
        // Discount percentage (0% - 50%)
        $discountPercentage = $this->faker->randomFloat(2, 0, 50);
        $discountSellingPriceFloat = $sellingPriceFloat * (1 - ($discountPercentage / 100));
        
        // Get a random product
        $product = Product::inRandomOrder()->first() ?? Product::factory()->create();
        
        // Get a random tenant
        $tenant = Tenant::inRandomOrder()->first() ?? Tenant::factory()->create();
        
        // Get a random unit of measure for this tenant
        $unitOfMeasure = UnitOfMeasure::where('tenant_id', $tenant->id)
            ->inRandomOrder()
            ->first() ?? UnitOfMeasure::factory()->create(['tenant_id' => $tenant->id]);
        
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
            // ── Identification ────────────────────────────────────────────────
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####')),
            'name' => $this->faker->word() . ' ' . $this->faker->randomElement(['Standard', 'Premium', 'Deluxe', 'Basic', 'Pro']),
            'barcode' => $this->faker->ean13(),
            
            // ── Cost & Pricing ────────────────────────────────────────────────
            'supplier_cost_price' => to_base_currency($supplierCostFloat),
            'total_shipping_cost' => to_base_currency($shippingCostFloat),
            'ura_taxes_applied' => to_base_currency($taxesFloat),
            'additional_expenses' => to_base_currency($expensesFloat),
            'grand_total_cost_price' => to_base_currency($grandTotalCostFloat),
            'selling_price' => to_base_currency($sellingPriceFloat),
            'discount_selling_price' => to_base_currency($discountSellingPriceFloat),
            'discount_percentage' => $discountPercentage,
            'markup_percentage' => $markupPercentage,
            
            // ── Inventory ──────────────────────────────────────────────────────
            'overal_quantity_at_hand' => $this->faker->randomElement([10, 22, 43, 35, 50, 300, 74, 100, 0, 5, 15]),
            
            // ── Physical Attributes ───────────────────────────────────────────
            'weight' => $this->faker->randomFloat(2, 50, 2000),
            'weight_unit' => $unitOfMeasure->id,
            
            // ── Media & Status ────────────────────────────────────────────────
            'image_url' => $this->faker->imageUrl(640, 480, 'products', true),
            'is_active' => $this->faker->boolean(90),
            'is_taxable' => $this->faker->boolean(80),
            
            // ── Relationships ──────────────────────────────────────────────────
            'product_id' => $product->id,
            'tenant_id' => $tenant->id,
            'created_by' => $user->id,
        ];
    }

    /**
     * Configure the model factory for a specific tenant.
     */
    public function forTenant(Tenant $tenant): self
    {
        return $this->state(function (array $attributes) use ($tenant) {
            // Get a unit of measure for this tenant
            $unitOfMeasure = UnitOfMeasure::where('tenant_id', $tenant->id)
                ->inRandomOrder()
                ->first() ?? UnitOfMeasure::factory()->create(['tenant_id' => $tenant->id]);
            
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
                'tenant_id' => $tenant->id,
                'weight_unit' => $unitOfMeasure->id,
                'created_by' => $user->id,
            ];
        });
    }

    /**
     * Configure the model factory for a specific product.
     */
    public function forProduct(Product $product): self
    {
        return $this->state(function (array $attributes) use ($product) {
            return [
                'product_id' => $product->id,
            ];
        });
    }

    /**
     * State for variants with high markup.
     */
    public function highMarkup(): self
    {
        return $this->state(function (array $attributes) {
            $markupPercentage = $this->faker->randomFloat(2, 150, 300);
            $grandTotalCost = $this->faker->randomFloat(2, 10, 50);
            $sellingPrice = $grandTotalCost * (1 + ($markupPercentage / 100));
            
            return [
                'markup_percentage' => $markupPercentage,
                'selling_price' => to_base_currency($sellingPrice),
            ];
        });
    }

    /**
     * State for variants with discount.
     */
    public function withDiscount(): self
    {
        return $this->state(function (array $attributes) {
            $discountPercentage = $this->faker->randomFloat(2, 10, 50);
            $sellingPrice = $attributes['selling_price'] ?? 0;
            $discountedPrice = $sellingPrice * (1 - ($discountPercentage / 100));
            
            return [
                'discount_percentage' => $discountPercentage,
                'discount_selling_price' => to_base_currency($discountedPrice),
            ];
        });
    }

    /**
     * State for variants with no discount.
     */
    public function noDiscount(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'discount_percentage' => 0,
                'discount_selling_price' => $attributes['selling_price'] ?? 0,
            ];
        });
    }

    /**
     * State for variants that are out of stock.
     */
    public function outOfStock(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'overal_quantity_at_hand' => 0,
                'is_active' => false,
            ];
        });
    }

    /**
     * State for variants that are in stock.
     */
    public function inStock(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'overal_quantity_at_hand' => $this->faker->randomElement([10, 25, 50, 100, 200, 500]),
                'is_active' => true,
            ];
        });
    }

    /**
     * State for variants with custom costs.
     */
    public function withCustomCosts(float $supplierCost, float $shipping = 0, float $taxes = 0, float $expenses = 0): self
    {
        return $this->state(function (array $attributes) use ($supplierCost, $shipping, $taxes, $expenses) {
            $grandTotal = $supplierCost + $shipping + $taxes + $expenses;
            
            return [
                'supplier_cost_price' => to_base_currency($supplierCost),
                'total_shipping_cost' => to_base_currency($shipping),
                'ura_taxes_applied' => to_base_currency($taxes),
                'additional_expenses' => to_base_currency($expenses),
                'grand_total_cost_price' => to_base_currency($grandTotal),
            ];
        });
    }
}