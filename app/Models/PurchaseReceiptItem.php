<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasTenant;


class PurchaseReceiptItem extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'purchase_receipt_id',
        'purchase_order_item_id',
        'quantity_received',
        'quantity_remaining',
        'location_id',
        'department_id',

        // Cost side
        'unit_cost',
        'grand_total_cost_price',

        // ★ Selling side
        'unit_selling_price',
        'discount_selling_price',
        'discount_percentage',
        'pricing_source',

        'batch_number',
        'expiry_date',
        'tenant_id',
    ];

    protected $casts = [
        'quantity_received'  => 'decimal:2',
        'quantity_remaining' => 'decimal:2',
        'expiry_date'        => 'date',

        // Money — base-currency integers
        'unit_cost'              => 'integer',
        'grand_total_cost_price' => 'integer',
        'unit_selling_price'     => 'integer',
        'discount_selling_price' => 'integer',
        'discount_percentage'    => 'decimal:2',
    ];

    // Accessors
    public function getUnitCostAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }
    public function getGrandTotalCostPriceAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }
    public function getUnitSellingPriceAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }
    public function getDiscountSellingPriceAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }

    // Mutators
    public function setUnitCostAttribute($v): void
    {
        $this->attributes['unit_cost'] = $v === null ? null : to_base_currency($v);
    }
    public function setGrandTotalCostPriceAttribute($v): void
    {
        $this->attributes['grand_total_cost_price'] = $v === null ? null : to_base_currency($v);
    }
    public function setUnitSellingPriceAttribute($v): void
    {
        $this->attributes['unit_selling_price'] = $v === null ? null : to_base_currency($v);
    }
    public function setDiscountSellingPriceAttribute($v): void
    {
        $this->attributes['discount_selling_price'] = $v === null ? null : to_base_currency($v);
    }

    /**
     * Effective selling price with fallback to the variant.
     */
    public function getEffectiveSellingPriceAttribute(): float
    {
        if ($this->discount_selling_price !== null) return (float) $this->discount_selling_price;
        if ($this->unit_selling_price    !== null) return (float) $this->unit_selling_price;

        $v = $this->purchaseOrderItem?->productVariant;
        return (float) ($v->discount_selling_price ?? $v->selling_price ?? 0);
    }

    public function getEffectiveCostPriceAttribute(): float
    {
        if ($this->grand_total_cost_price !== null) return (float) $this->grand_total_cost_price;
        if ($this->unit_cost              !== null) return (float) $this->unit_cost;

        return (float) ($this->purchaseOrderItem?->productVariant?->grand_total_cost_price ?? 0);
    }

    public function purchaseReceipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceipt::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}