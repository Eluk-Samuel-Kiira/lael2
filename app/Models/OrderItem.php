<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasTenant;

class OrderItem extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'item_name',
        'sku',

        // Selling side
        'unit_price',
        'quantity',
        'discount',
        'tax_amount',
        'total_price',

        // ★ Cost side (frozen at time of sale)
        'unit_cost_price',
        'total_cost_price',
        'gross_profit',
        'pricing_source',

        // Fulfilment tracking
        'batch_id',
        'batch_number',
        'serial_id',
        'serial_number',

        // Snapshots
        'inventory_data',
        'tax_data',
        'promotion_data',
        'refunded_quantity',

        'tenant_id',
    ];

    protected $casts = [
        // Selling money — stored as base-currency integers
        'unit_price'       => 'integer',
        'discount'         => 'integer',
        'tax_amount'       => 'integer',
        'total_price'      => 'integer',

        // ★ Cost money — same convention
        'unit_cost_price'  => 'integer',
        'total_cost_price' => 'integer',
        'gross_profit'     => 'integer',

        // Structured columns
        'inventory_data' => 'array',
        'tax_data'       => 'array',
        'promotion_data' => 'array',

        'batch_id' => 'integer',
        'serial_id' => 'integer',
        'refunded_quantity' => 'float',
    ];



    // ─── Selling-side accessors (base → display) ──────────────

    public function getUnitPriceAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getDiscountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getTaxAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getTotalPriceAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    // ─── Cost-side accessors (base → display) ─────────────────

    public function getUnitCostPriceAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }

    public function getTotalCostPriceAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }

    public function getGrossProfitAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }

    // ─── Selling-side mutators (display → base) ───────────────

    public function setUnitPriceAttribute($value): void
    {
        $this->attributes['unit_price'] = to_base_currency($value);
    }

    public function setDiscountAttribute($value): void
    {
        $this->attributes['discount'] = to_base_currency($value);
    }

    public function setTaxAmountAttribute($value): void
    {
        $this->attributes['tax_amount'] = to_base_currency($value);
    }

    public function setTotalPriceAttribute($value): void
    {
        $this->attributes['total_price'] = to_base_currency($value);
    }

    // ─── Cost-side mutators (display → base) ──────────────────

    public function setUnitCostPriceAttribute($value): void
    {
        $this->attributes['unit_cost_price'] = $value === null
            ? null
            : to_base_currency($value);
    }

    public function setTotalCostPriceAttribute($value): void
    {
        $this->attributes['total_cost_price'] = $value === null
            ? null
            : to_base_currency($value);
    }

    public function setGrossProfitAttribute($value): void
    {
        $this->attributes['gross_profit'] = $value === null
            ? null
            : to_base_currency($value);
    }

    // ─── Derived helpers ──────────────────────────────────────

    /**
     * Margin as a percentage of net revenue.
     * Null until cost is set.
     */
    public function getProfitMarginAttribute(): ?float
    {
        $net = (float) ($this->total_price ?? 0);
        if ($net <= 0) return null;

        $cost = (float) ($this->total_cost_price ?? 0);

        return (($net - $cost) / $net) * 100;
    }

    // ─── Refund tracking helpers ──────────────────────────────

    /**
     * How much of this line item can still be refunded.
     * Always clamped to [0, quantity] even if the DB has drifted.
     */
    public function getRefundableQuantityAttribute(): float
    {
        $qty       = (float) ($this->quantity ?? 0);
        $refunded  = (float) ($this->refunded_quantity ?? 0);

        return max(0, $qty - $refunded);
    }

    /**
     * True if every unit of this line item has already been refunded.
     */
    public function isFullyRefunded(): bool
    {
        return (float) ($this->refunded_quantity ?? 0) >= (float) ($this->quantity ?? 0) - 0.0001;
    }

    /**
     * True if at least one unit has been refunded, but not all.
     */
    public function isPartiallyRefunded(): bool
    {
        $refunded = (float) ($this->refunded_quantity ?? 0);
        return $refunded > 0.0001 && !$this->isFullyRefunded();
    }

    /**
     * Mutator — clamps refunded_quantity to [0, quantity] on write.
     * Because this bypasses `$fillable`, it will fire on both
     * `$item->refunded_quantity = X` and `$item->update(['refunded_quantity' => X])`.
     *
     * Defensive: even a controller bug can't write refunded_quantity > quantity.
     */
    public function setRefundedQuantityAttribute($value): void
    {
        $q = (float) $value;
        if ($q < 0) $q = 0;

        // Read the raw quantity from attributes (avoids accessor round-trip)
        $max = (float) ($this->attributes['quantity'] ?? 0);
        if ($q > $max) $q = $max;

        $this->attributes['refunded_quantity'] = $q;
    }

    /**
     * Convenience: was this line sold using an inventory-item-level
     * price override, or inherited from the variant?
     */
    public function hasCustomCost(): bool
    {
        return $this->pricing_source === 'item';
    }

    // ─── Auto-compute cost + profit on save ───────────────────

    /**
     * When an order item is created with a unit_cost_price but no
     * derived values, compute them automatically.
     *
     * total_cost_price = unit_cost_price × quantity
     * gross_profit     = (total_price - total_cost_price) - tax_amount
     *
     * Note we subtract tax_amount so "profit" reflects what the
     * business keeps after tax is remitted — not pre-tax revenue
     * minus cost. If your accounting treats tax separately, drop
     * the tax_amount term.
     */
    protected static function booted(): void
    {
        static::saving(function (self $item) {
            // Only auto-compute when we actually have a unit cost.
            if ($item->getRawOriginal('unit_cost_price') !== null
                || $item->isDirty('unit_cost_price')) {

                $unitCost = (float) ($item->unit_cost_price ?? 0);
                $qty      = (float) ($item->quantity ?? 0);

                if (! $item->isDirty('total_cost_price')) {
                    $item->total_cost_price = $unitCost * $qty;
                }

                if (! $item->isDirty('gross_profit')) {
                    $netRevenue = (float) ($item->total_price ?? 0)
                                - (float) ($item->tax_amount ?? 0);
                    $item->gross_profit = $netRevenue - (float) $item->total_cost_price;
                }
            }
        });
    }

    // ─── Relationships ────────────────────────────────────────

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function serialNumber()
    {
        return $this->belongsTo(SerialNumber::class, 'serial_id');
    }

    public function getReturnValueAttribute()
    {
        return $this->total_price;
    }
}