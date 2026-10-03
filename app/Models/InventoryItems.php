<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenant;

class InventoryItems extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryItemsFactory> */
    use HasFactory, HasTenant;

    protected $fillable = [
        'quantity_on_hand',
        'quantity_allocated',
        'quantity_on_order',
        'reorder_point',
        'preferred_stock_level',
        'batch_number',
        'expiry_date',
        'variant_id',
        'location_id',
        'department_id',
        'created_by',
        'tenant_id',

        // ★ Per-scope pricing overrides
        'supplier_cost_price',
        'total_shipping_cost',
        'ura_taxes_applied',
        'additional_expenses',
        'grand_total_cost_price',
        'selling_price',
        'discount_selling_price',
        'discount_percentage',
        'markup_percentage',
        'has_custom_pricing',
    ];

    protected $casts = [
        // Money fields stored as integers in DB
        'supplier_cost_price'    => 'integer',
        'total_shipping_cost'    => 'integer',
        'ura_taxes_applied'      => 'integer',
        'additional_expenses'    => 'integer',
        'grand_total_cost_price' => 'integer',
        'selling_price'          => 'integer',
        'discount_selling_price' => 'integer',
        'discount_percentage'    => 'decimal:2',
        'markup_percentage'      => 'decimal:2',
        'has_custom_pricing'     => 'boolean',
    ];

    // ─── Accessors (base currency → display currency) ─────────

    public function getSupplierCostPriceAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }
    public function getTotalShippingCostAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }
    public function getUraTaxesAppliedAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }
    public function getAdditionalExpensesAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }
    public function getGrandTotalCostPriceAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }
    public function getSellingPriceAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }
    public function getDiscountSellingPriceAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }

    // ─── Mutators (display currency → base currency) ──────────

    public function setSupplierCostPriceAttribute($value): void
    {
        $this->attributes['supplier_cost_price'] = $value === null ? null : to_base_currency($value);
    }
    public function setTotalShippingCostAttribute($value): void
    {
        $this->attributes['total_shipping_cost'] = $value === null ? null : to_base_currency($value);
    }
    public function setUraTaxesAppliedAttribute($value): void
    {
        $this->attributes['ura_taxes_applied'] = $value === null ? null : to_base_currency($value);
    }
    public function setAdditionalExpensesAttribute($value): void
    {
        $this->attributes['additional_expenses'] = $value === null ? null : to_base_currency($value);
    }
    public function setGrandTotalCostPriceAttribute($value): void
    {
        $this->attributes['grand_total_cost_price'] = $value === null ? null : to_base_currency($value);
    }
    public function setSellingPriceAttribute($value): void
    {
        $this->attributes['selling_price'] = $value === null ? null : to_base_currency($value);
    }
    public function setDiscountSellingPriceAttribute($value): void
    {
        $this->attributes['discount_selling_price'] = $value === null ? null : to_base_currency($value);
    }

    // ─── Effective pricing (fallback to parent variant) ───────

    /**
     * The actual cost to use in reports, falling back to the variant.
     */
    public function getEffectiveGrandTotalCostPriceAttribute(): float
    {
        $own = (float) ($this->grand_total_cost_price ?? 0);
        if ($own > 0) return $own;

        // Fall back to variant
        $v = $this->variant;
        if (!$v) return 0.0;

        $grand = (float) ($v->grand_total_cost_price ?? 0);
        if ($grand > 0) return $grand;

        // Last resort — recompute from components
        return (float) ($v->supplier_cost_price ?? 0)
             + (float) ($v->total_shipping_cost ?? 0)
             + (float) ($v->ura_taxes_applied ?? 0)
             + (float) ($v->additional_expenses ?? 0);
    }

    public function getEffectiveSellingPriceAttribute(): float
    {
        $own = (float) ($this->selling_price ?? 0);
        if ($own > 0) return $own;

        $v = $this->variant;
        return (float) ($v->selling_price ?? 0);
    }

    public function getEffectiveDiscountSellingPriceAttribute(): float
    {
        $own = (float) ($this->discount_selling_price ?? 0);
        if ($own > 0) return $own;

        $v = $this->variant;
        if (!$v) return $this->effective_selling_price;

        return (float) ($v->discount_selling_price
            ?? $v->selling_price
            ?? 0);
    }

    /**
     * Profit per unit based on effective pricing.
     */
    public function getEffectiveProfitPerUnitAttribute(): float
    {
        return $this->effective_discount_selling_price
             - $this->effective_grand_total_cost_price;
    }

    /**
     * Profit margin % based on effective pricing.
     */
    public function getEffectiveProfitMarginAttribute(): float
    {
        $net = $this->effective_discount_selling_price;
        if ($net <= 0) return 0.0;
        return ($this->effective_profit_per_unit / $net) * 100;
    }

    // ─── Override management ──────────────────────────────────

    /**
     * Compute grand total from components — useful when a user
     * sets per-scope prices on the item.
     */
    public function calculateGrandTotalCostPrice(): float
    {
        return (float) ($this->supplier_cost_price ?? 0)
             + (float) ($this->total_shipping_cost ?? 0)
             + (float) ($this->ura_taxes_applied ?? 0)
             + (float) ($this->additional_expenses ?? 0);
    }

    /**
     * Flip has_custom_pricing to match whether any of the override
     * columns are populated. Safe to call before save().
     */
    public function syncCustomPricingFlag(): self
    {
        $this->has_custom_pricing = (
            $this->supplier_cost_price    !== null
            || $this->total_shipping_cost !== null
            || $this->ura_taxes_applied   !== null
            || $this->additional_expenses !== null
            || $this->grand_total_cost_price !== null
            || $this->selling_price        !== null
            || $this->discount_selling_price !== null
        );
        return $this;
    }

    /**
     * Clear all override columns — the item goes back to inheriting
     * from its parent variant.
     */
    public function clearCustomPricing(): self
    {
        $this->supplier_cost_price    = null;
        $this->total_shipping_cost    = null;
        $this->ura_taxes_applied      = null;
        $this->additional_expenses    = null;
        $this->grand_total_cost_price = null;
        $this->selling_price          = null;
        $this->discount_selling_price = null;
        $this->discount_percentage    = null;
        $this->markup_percentage      = null;
        $this->has_custom_pricing     = false;
        return $this;
    }

    /**
     * Auto-sync flag on every save. Also recomputes grand_total and
     * discount_selling_price from components when values are set.
     */
    protected static function booted(): void
    {
        static::saving(function (self $item) {
            // Auto-compute grand total if any component is set
            if ($item->supplier_cost_price    !== null
                || $item->total_shipping_cost !== null
                || $item->ura_taxes_applied   !== null
                || $item->additional_expenses !== null) {
                if (!$item->isDirty('grand_total_cost_price')) {
                    $item->grand_total_cost_price = $item->calculateGrandTotalCostPrice();
                }
            }

            // Auto-compute discounted selling price when a % is set
            if ($item->selling_price !== null
                && $item->discount_percentage !== null
                && (float) $item->discount_percentage > 0) {
                if (!$item->isDirty('discount_selling_price')) {
                    $item->discount_selling_price =
                        $item->selling_price
                        - (($item->selling_price * (float) $item->discount_percentage) / 100);
                }
            }

            $item->syncCustomPricingFlag();
        });
    }

    // ─── Relationships ────────────────────────────────────────

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'id');
    }

    public function departmentItem()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function itemCreater()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function itemLocation()
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }
}