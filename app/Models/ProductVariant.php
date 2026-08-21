<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasTenant;


class ProductVariant extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariantFactory> */
    use HasFactory, HasTenant;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'barcode',
        'supplier_cost_price',
        'total_shipping_cost',
        'ura_taxes_applied',
        'additional_expenses',
        'grand_total_cost_price',
        'selling_price',
        'discount_selling_price',
        'discount_percentage',
        'markup_percentage',
        'overal_quantity_at_hand',
        'low_stock_level',
        'weight',
        'weight_unit',
        'image_url',
        'is_active',
        'is_taxable',
        'created_by',
        'tenant_id',
    ];

    protected $casts = [
        // Money fields - stored as integers in DB
        'supplier_cost_price' => 'integer',
        'total_shipping_cost' => 'integer',
        'ura_taxes_applied' => 'integer',
        'additional_expenses' => 'integer',
        'grand_total_cost_price' => 'integer',
        'selling_price' => 'integer',
        'discount_selling_price' => 'integer',
        'discount_percentage' => 'decimal:2',
        'markup_percentage' => 'decimal:2',
        'weight' => 'decimal:2',
        'is_active' => 'boolean',
        'is_taxable' => 'boolean',
    ];

    // ─── Accessors ────────────────────────────────────────────

    public function getSupplierCostPriceAttribute($value): float
    {
        return from_base_currency($value);
    }

    public function getTotalShippingCostAttribute($value): float
    {
        return from_base_currency($value);
    }

    public function getUraTaxesAppliedAttribute($value): float
    {
        return from_base_currency($value);
    }

    public function getAdditionalExpensesAttribute($value): float
    {
        return from_base_currency($value);
    }

    public function getGrandTotalCostPriceAttribute($value): float
    {
        return from_base_currency($value);
    }

    public function getSellingPriceAttribute($value): float
    {
        return from_base_currency($value);
    }

    public function getDiscountSellingPriceAttribute($value): float
    {
        return from_base_currency($value);
    }

    // ─── Mutators ─────────────────────────────────────────────

    public function setSupplierCostPriceAttribute($value): void
    {
        $this->attributes['supplier_cost_price'] = to_base_currency($value);
    }

    public function setTotalShippingCostAttribute($value): void
    {
        $this->attributes['total_shipping_cost'] = to_base_currency($value);
    }

    public function setUraTaxesAppliedAttribute($value): void
    {
        $this->attributes['ura_taxes_applied'] = to_base_currency($value);
    }

    public function setAdditionalExpensesAttribute($value): void
    {
        $this->attributes['additional_expenses'] = to_base_currency($value);
    }

    public function setGrandTotalCostPriceAttribute($value): void
    {
        $this->attributes['grand_total_cost_price'] = to_base_currency($value);
    }

    public function setSellingPriceAttribute($value): void
    {
        $this->attributes['selling_price'] = to_base_currency($value);
    }

    public function setDiscountSellingPriceAttribute($value): void
    {
        $this->attributes['discount_selling_price'] = to_base_currency($value);
    }



    /**
     * Get the batches for this variant
     * Relationship path: ProductVariant -> PurchaseOrderItem -> PurchaseReceiptItem
     */
    public function batches()
    {
        return $this->hasManyThrough(
            PurchaseReceiptItem::class,        // Final model we want
            PurchaseOrderItem::class,          // Intermediate model
            'product_variant_id',              // Foreign key on purchase_order_items (matches ProductVariant.id)
            'purchase_order_item_id',          // Foreign key on purchase_receipt_items (matches PurchaseOrderItem.id)
            'id',                              // Local key on ProductVariant
            'id'                               // Local key on PurchaseOrderItem
        )
        ->where(function($q) {
            $q->where('purchase_receipt_items.quantity_remaining', '>', 0)
            ->orWhereNull('purchase_receipt_items.quantity_remaining');
        })
        ->orderBy('purchase_receipt_items.expiry_date', 'asc');
    }

    /**
     * Get available batches filtered by location and department
     */
    public function getAvailableBatches($locationId = null, $departmentId = null)
    {
        $query = $this->batches();
        
        if ($locationId) {
            $query->where('purchase_receipt_items.location_id', $locationId);
        }
        if ($departmentId) {
            $query->where('purchase_receipt_items.department_id', $departmentId);
        }
        
        return $query->get();
    }

    /**
     * Get total available quantity across all batches
     */
    public function getTotalBatchQuantity($locationId = null, $departmentId = null)
    {
        return $this->getAvailableBatches($locationId, $departmentId)
            ->sum(function($batch) {
                return $batch->quantity_remaining ?? $batch->quantity_received ?? 0;
            });
    }


    // ─── Business Logic ──────────────────────────────────────

    /**
     * ✅ Calculate grand total cost price
     */
    public function calculateGrandTotalCostPrice(): float
    {
        return ($this->supplier_cost_price ?? 0) 
            + ($this->total_shipping_cost ?? 0) 
            + ($this->ura_taxes_applied ?? 0) 
            + ($this->additional_expenses ?? 0);
    }

    /**
     * ✅ Calculate discount selling price after discount
     */
    public function calculateDiscountSellingPrice(): ?float
    {
        if ($this->selling_price && $this->discount_percentage > 0) {
            $discountAmount = ($this->selling_price * $this->discount_percentage) / 100;
            return $this->selling_price - $discountAmount;
        }
        return $this->selling_price;
    }

    /**
     * ✅ Calculate markup percentage from grand total cost to selling price
     */
    public function calculateMarkupPercentage(): float
    {
        if ($this->grand_total_cost_price && $this->grand_total_cost_price > 0 
            && $this->selling_price && $this->selling_price > 0) {
            return (($this->selling_price - $this->grand_total_cost_price) / $this->grand_total_cost_price) * 100;
        }
        return 0;
    }

    /**
     * ✅ Calculate profit margin from grand total cost
     */
    public function calculateProfitMargin(): float
    {
        if ($this->selling_price && $this->selling_price > 0) {
            return (($this->selling_price - $this->grand_total_cost_price) / $this->selling_price) * 100;
        }
        return 0;
    }

    /**
     * ✅ Get profit per unit (selling price - grand total cost)
     */
    public function getProfitPerUnitAttribute(): float
    {
        $netPrice = $this->discount_selling_price ?? $this->selling_price ?? 0;
        $grossCost = $this->grand_total_cost_price ?? 0;
        return $netPrice - $grossCost;
    }

    /**
     * ✅ Get profit margin percentage
     */
    public function getProfitMarginAttribute(): float
    {
        $netPrice = $this->discount_selling_price ?? $this->selling_price ?? 0;
        if ($netPrice > 0) {
            return ($this->profit_per_unit / $netPrice) * 100;
        }
        return 0;
    }

    /**
     * ✅ Get total expenses (grand total cost - supplier cost)
     */
    public function getTotalExpensesAttribute(): float
    {
        return ($this->grand_total_cost_price ?? 0) - ($this->supplier_cost_price ?? 0);
    }

    /**
     * ✅ Get discount amount
     */
    public function getDiscountAmountAttribute(): float
    {
        if ($this->selling_price && $this->discount_selling_price) {
            return $this->selling_price - $this->discount_selling_price;
        }
        return 0;
    }

    /**
     * ✅ Update discount selling price from selling price and discount
     */
    public function updateDiscountSellingPrice(): self
    {
        $this->discount_selling_price = $this->calculateDiscountSellingPrice();
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
     * ✅ Update grand total cost price
     */
    public function updateGrandTotalCostPrice(): self
    {
        $this->grand_total_cost_price = $this->calculateGrandTotalCostPrice();
        $this->save();
        return $this;
    }

    /**
     * ✅ Apply discount to variant
     */
    public function applyDiscount(float $percentage): self
    {
        $this->discount_percentage = $percentage;
        $this->discount_selling_price = $this->calculateDiscountSellingPrice();
        $this->save();
        return $this;
    }

    /**
     * ✅ Remove discount
     */
    public function removeDiscount(): self
    {
        $this->discount_percentage = 0;
        $this->discount_selling_price = $this->selling_price;
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
            ((`selling_price` - `grand_total_cost_price`) / `selling_price`) * 100 > ?',
            [$percentage]
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    

    /**
     * Get the serial numbers for this variant
     */
    public function serialNumbers()
    {
        return $this->hasMany(SerialNumber::class, 'variant_id'); // ✅ Explicit foreign key
    }

    /**
     * Get available serial numbers
     */
    public function getAvailableSerialNumbers($locationId = null, $departmentId = null)
    {
        $query = $this->serialNumbers()
            ->where('status', SerialNumber::STATUS_AVAILABLE)
            ->where('tenant_id', $this->tenant_id);
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        return $query->get();
    }

    /**
     * Get count of available serial numbers
     */
    public function getAvailableSerialCount($locationId = null, $departmentId = null)
    {
        return $this->getAvailableSerialNumbers($locationId, $departmentId)->count();
    }



}