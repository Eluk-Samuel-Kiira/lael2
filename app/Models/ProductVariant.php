<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariantFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'barcode',
        'price',                    // ✅ Selling price to customer
        'net_selling_price',        // ✅ Selling price after discounts
        'net_cost_price',           // ✅ Supplier cost price (what you pay supplier)
        'cost_price',               // ✅ Gross cost price (net_cost + shipping/expenses)
        'discount_percentage',      
        'markup_percentage',         
        'weight',
        'weight_unit',
        'image_url',
        'is_active',
        'created_by',
        'tenant_id',
        'overal_quantity_at_hand',
        'is_taxable',
    ];

    protected $casts = [
        'price' => 'integer',              
        'net_selling_price' => 'integer',  
        'net_cost_price' => 'integer',     // ✅ Supplier cost
        'cost_price' => 'integer',         // ✅ Gross cost (with expenses)
        'discount_percentage' => 'decimal:2',
        'markup_percentage' => 'decimal:2',   
        'weight' => 'decimal:2',
    ];

    // ─── Accessors ────────────────────────────────────────────

    /**
     * ✅ Selling price to customer (with markup)
     */
    public function getPriceAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    /**
     * ✅ Gross cost price (net_cost + shipping + expenses)
     */
    public function getCostPriceAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    /**
     * ✅ Supplier cost price (what you pay the supplier)
     */
    public function getNetCostPriceAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    /**
     * ✅ Net selling price (after discounts)
     */
    public function getNetSellingPriceAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    // ─── Mutators ─────────────────────────────────────────────

    public function setPriceAttribute($value): void
    {
        $this->attributes['price'] = to_base_currency($value);
    }

    public function setCostPriceAttribute($value): void
    {
        $this->attributes['cost_price'] = to_base_currency($value);
    }

    public function setNetCostPriceAttribute($value): void
    {
        $this->attributes['net_cost_price'] = to_base_currency($value);
    }

    public function setNetSellingPriceAttribute($value): void
    {
        $this->attributes['net_selling_price'] = to_base_currency($value);
    }

    // ─── Business Logic ──────────────────────────────────────

    /**
     * ✅ Calculate gross cost price (net_cost + expenses)
     * @param float $expenses - Additional expenses (shipping, taxes, etc.)
     */
    public function calculateGrossCostPrice(float $expenses = 0): float
    {
        return ($this->net_cost_price ?? 0) + $expenses;
    }

    /**
     * ✅ Calculate net selling price after discount
     */
    public function calculateNetSellingPrice(): ?float
    {
        if ($this->price && $this->discount_percentage > 0) {
            $discountAmount = ($this->price * $this->discount_percentage) / 100;
            return $this->price - $discountAmount;
        }
        return $this->price;
    }

    /**
     * ✅ Calculate markup percentage from gross cost to selling price
     */
    public function calculateMarkupPercentage(): float
    {
        if ($this->cost_price && $this->cost_price > 0 && $this->price && $this->price > 0) {
            return (($this->price - $this->cost_price) / $this->cost_price) * 100;
        }
        return 0;
    }

    /**
     * ✅ Calculate profit margin from gross cost
     */
    public function calculateProfitMargin(): float
    {
        if ($this->price && $this->price > 0) {
            return (($this->price - $this->cost_price) / $this->price) * 100;
        }
        return 0;
    }

    /**
     * ✅ Get profit per unit (net selling - gross cost)
     */
    public function getProfitPerUnitAttribute(): float
    {
        $netPrice = $this->net_selling_price ?? $this->price ?? 0;
        $grossCost = $this->cost_price ?? 0;
        return $netPrice - $grossCost;
    }

    /**
     * ✅ Get profit margin percentage
     */
    public function getProfitMarginAttribute(): float
    {
        $netPrice = $this->net_selling_price ?? $this->price ?? 0;
        if ($netPrice > 0) {
            return ($this->profit_per_unit / $netPrice) * 100;
        }
        return 0;
    }

    /**
     * ✅ Get supplier cost (net cost)
     */
    public function getSupplierCostAttribute(): float
    {
        return $this->net_cost_price ?? 0;
    }

    /**
     * ✅ Get gross cost (net cost + expenses)
     */
    public function getGrossCostAttribute(): float
    {
        return $this->cost_price ?? 0;
    }

    /**
     * ✅ Get total expenses (gross cost - net cost)
     */
    public function getExpensesAttribute(): float
    {
        return ($this->cost_price ?? 0) - ($this->net_cost_price ?? 0);
    }

    /**
     * ✅ Get discount amount
     */
    public function getDiscountAmountAttribute(): float
    {
        if ($this->price && $this->net_selling_price) {
            return $this->price - $this->net_selling_price;
        }
        return 0;
    }

    /**
     * ✅ Update net selling price from price and discount
     */
    public function updateNetSellingPrice(): self
    {
        $this->net_selling_price = $this->calculateNetSellingPrice();
        $this->save();
        return $this;
    }

    /**
     * ✅ Update markup percentage
     */
    public function updateMarkupPercentage(): self
    {
        $this->markup_percentage = $this->calculateMarkupPercentage();
        $this->save();
        return $this;
    }

    /**
     * ✅ Update gross cost price from net cost + expenses
     */
    public function updateGrossCostPrice(float $expenses = 0): self
    {
        $this->cost_price = $this->calculateGrossCostPrice($expenses);
        $this->save();
        return $this;
    }

    /**
     * ✅ Apply discount to variant
     */
    public function applyDiscount(float $percentage): self
    {
        $this->discount_percentage = $percentage;
        $this->net_selling_price = $this->calculateNetSellingPrice();
        $this->save();
        return $this;
    }

    /**
     * ✅ Remove discount
     */
    public function removeDiscount(): self
    {
        $this->discount_percentage = 0;
        $this->net_selling_price = $this->price;
        $this->save();
        return $this;
    }

    // ─── Relationships ──────────────────────────────────────

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
    
    public function unitMeasure()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'weight_unit', 'id');
    }

    public function variantCreater()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    
    public function inventory()
    {   
        return $this->hasMany(InventoryItems::class, 'variant_id', 'id');
    }

    public function variantTaxes()
    {
        return $this->belongsToMany(Tax::class, 'variant_taxes', 'variant_id', 'tax_id')
                    ->withPivot(['created_by', 'tenant_id'])
                    ->withTimestamps();
    }

    public function variantPromotions()
    {
        return $this->belongsToMany(Promotion::class, 'promotion_products', 'variant_id', 'promotion_id');
    }

    public function ingredientRecipes(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class, 'ingredient_variant_id');
    }

    public function outputProductionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class, 'output_product_variant_id');
    }

    public function productionOrderInputs(): HasMany
    {
        return $this->hasMany(ProductionOrderInput::class, 'input_variant_id');
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeWithDiscount($query)
    {
        return $query->where('discount_percentage', '>', 0);
    }

    public function scopeWithoutDiscount($query)
    {
        return $query->where('discount_percentage', 0);
    }

    public function scopeWithProfitMarginAbove($query, float $percentage)
    {
        return $query->whereRaw('
            ((`price` - `cost_price`) / `price`) * 100 > ?',
            [$percentage]
        );
    }
}