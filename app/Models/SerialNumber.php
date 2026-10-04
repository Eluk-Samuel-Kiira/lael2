<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Traits\HasTenant;

class SerialNumber extends Model
{
    use HasFactory, HasTenant;

    const STATUS_AVAILABLE = 'available';
    const STATUS_SOLD      = 'sold';
    const STATUS_RESERVED  = 'reserved';
    const STATUS_RETURNED  = 'returned';
    const STATUS_LOST      = 'lost';
    const STATUS_DAMAGED   = 'damaged';

    protected $fillable = [
        'variant_id',
        'tenant_id',
        'serial_number',
        'status',
        'order_id',
        'sold_at',
        'sold_by',
        'location_id',
        'department_id',
        'purchase_order_id',
        'purchase_receipt_id',
        'batch_id',
        'expiry_date',
        'notes',
        'created_by',

        // ★ Pricing — serial-level override
        'supplier_cost_price',
        'total_shipping_cost',
        'ura_taxes_applied',
        'additional_expenses',
        'grand_total_cost_price',
        'selling_price',
        'discount_selling_price',
        'discount_percentage',
        'markup_percentage',

        // ★ Sale snapshot (frozen at sale time)
        'sold_unit_price',
        'sold_total_price',
        'sold_gross_profit',

        // ★ Audit
        'pricing_source',
        'has_custom_pricing',
    ];

    protected $casts = [
        'sold_at'             => 'datetime',
        'expiry_date'         => 'date',

        // Money fields — base-currency integers
        'supplier_cost_price'    => 'integer',
        'total_shipping_cost'    => 'integer',
        'ura_taxes_applied'      => 'integer',
        'additional_expenses'    => 'integer',
        'grand_total_cost_price' => 'integer',
        'selling_price'          => 'integer',
        'discount_selling_price' => 'integer',
        'sold_unit_price'        => 'integer',
        'sold_total_price'       => 'integer',
        'sold_gross_profit'      => 'integer',

        'discount_percentage' => 'decimal:2',
        'markup_percentage'   => 'decimal:2',
        'has_custom_pricing'  => 'boolean',
    ];

    // ─── Money accessors (base → display) ──────────────────────

    public function getSupplierCostPriceAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }
    public function getTotalShippingCostAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }
    public function getUraTaxesAppliedAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }
    public function getAdditionalExpensesAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }
    public function getGrandTotalCostPriceAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }
    public function getSellingPriceAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }
    public function getDiscountSellingPriceAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }
    public function getSoldUnitPriceAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }
    public function getSoldTotalPriceAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }
    public function getSoldGrossProfitAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }

    // ─── Money mutators (display → base) ───────────────────────

    public function setSupplierCostPriceAttribute($v): void
    {
        $this->attributes['supplier_cost_price'] = $v === null ? null : to_base_currency($v);
    }
    public function setTotalShippingCostAttribute($v): void
    {
        $this->attributes['total_shipping_cost'] = $v === null ? null : to_base_currency($v);
    }
    public function setUraTaxesAppliedAttribute($v): void
    {
        $this->attributes['ura_taxes_applied'] = $v === null ? null : to_base_currency($v);
    }
    public function setAdditionalExpensesAttribute($v): void
    {
        $this->attributes['additional_expenses'] = $v === null ? null : to_base_currency($v);
    }
    public function setGrandTotalCostPriceAttribute($v): void
    {
        $this->attributes['grand_total_cost_price'] = $v === null ? null : to_base_currency($v);
    }
    public function setSellingPriceAttribute($v): void
    {
        $this->attributes['selling_price'] = $v === null ? null : to_base_currency($v);
    }
    public function setDiscountSellingPriceAttribute($v): void
    {
        $this->attributes['discount_selling_price'] = $v === null ? null : to_base_currency($v);
    }
    public function setSoldUnitPriceAttribute($v): void
    {
        $this->attributes['sold_unit_price'] = $v === null ? null : to_base_currency($v);
    }
    public function setSoldTotalPriceAttribute($v): void
    {
        $this->attributes['sold_total_price'] = $v === null ? null : to_base_currency($v);
    }
    public function setSoldGrossProfitAttribute($v): void
    {
        $this->attributes['sold_gross_profit'] = $v === null ? null : to_base_currency($v);
    }

    // ─── Effective pricing (item → variant → 0) ────────────────
    //
    // Resolution order for what this serial is currently priced at:
    //   1. Serial's own override
    //   2. InventoryItems override for this serial's location + department
    //   3. Parent variant's price
    //   4. 0

    public function getEffectiveGrandTotalCostPriceAttribute(): float
    {
        if ($this->grand_total_cost_price !== null) {
            return (float) $this->grand_total_cost_price;
        }

        $item = $this->resolveInventoryItem();
        if ($item && $item->grand_total_cost_price !== null) {
            return (float) $item->grand_total_cost_price;
        }

        $variant = $this->variant;
        if (! $variant) return 0.0;

        $grand = (float) ($variant->grand_total_cost_price ?? 0);
        if ($grand > 0) return $grand;

        return (float) ($variant->supplier_cost_price ?? 0)
             + (float) ($variant->total_shipping_cost ?? 0)
             + (float) ($variant->ura_taxes_applied   ?? 0)
             + (float) ($variant->additional_expenses ?? 0);
    }

    public function getEffectiveSellingPriceAttribute(): float
    {
        if ($this->selling_price !== null) {
            return (float) $this->selling_price;
        }

        $item = $this->resolveInventoryItem();
        if ($item && $item->selling_price !== null) {
            return (float) $item->selling_price;
        }

        return (float) ($this->variant->selling_price ?? 0);
    }

    public function getEffectiveDiscountSellingPriceAttribute(): float
    {
        if ($this->discount_selling_price !== null) {
            return (float) $this->discount_selling_price;
        }

        $item = $this->resolveInventoryItem();
        if ($item) {
            $price = (float) ($item->discount_selling_price
                ?? $item->selling_price
                ?? 0);
            if ($price > 0) return $price;
        }

        $v = $this->variant;
        return (float) ($v->discount_selling_price ?? $v->selling_price ?? 0);
    }

    /**
     * Serial has been sold — use the frozen snapshot.
     */
    public function getEffectiveSoldPriceAttribute(): float
    {
        return (float) ($this->sold_unit_price
            ?? $this->effective_discount_selling_price);
    }


    /**
     * Find the InventoryItems row that matches this serial's
     * variant + location + department, if any.
     */
    protected function resolveInventoryItem(): ?InventoryItems
    {
        if (! $this->variant_id) return null;

        return InventoryItems::query()
            ->where('tenant_id', $this->tenant_id)
            ->where('variant_id', $this->variant_id)
            ->when($this->location_id,   fn($q) => $q->where('location_id', $this->location_id))
            ->when($this->department_id, fn($q) => $q->where('department_id', $this->department_id))
            ->first();
    }

    // ─── Auto-compute on write ─────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (self $serial) {
            // Auto grand total if components provided
            if ($serial->supplier_cost_price    !== null
                || $serial->total_shipping_cost !== null
                || $serial->ura_taxes_applied   !== null
                || $serial->additional_expenses !== null) {
                if (! $serial->isDirty('grand_total_cost_price')) {
                    $serial->grand_total_cost_price =
                        (float) ($serial->supplier_cost_price    ?? 0)
                      + (float) ($serial->total_shipping_cost    ?? 0)
                      + (float) ($serial->ura_taxes_applied      ?? 0)
                      + (float) ($serial->additional_expenses    ?? 0);
                }
            }

            // Auto discounted price from selling + %
            if ($serial->selling_price !== null
                && $serial->discount_percentage !== null
                && (float) $serial->discount_percentage > 0) {
                if (! $serial->isDirty('discount_selling_price')) {
                    $serial->discount_selling_price =
                        $serial->selling_price
                        - (($serial->selling_price * (float) $serial->discount_percentage) / 100);
                }
            }

            // ★ Freeze sold prices when status flips to sold
            if ($serial->status === self::STATUS_SOLD) {
                if ($serial->sold_unit_price === null) {
                    $serial->sold_unit_price = $serial->effective_discount_selling_price;
                }
                if ($serial->sold_total_price === null) {
                    $serial->sold_total_price = (float) $serial->sold_unit_price;
                }
                if ($serial->sold_gross_profit === null) {
                    $serial->sold_gross_profit =
                        (float) $serial->sold_total_price
                      - $serial->effective_grand_total_cost_price;
                }
            }

            // has_custom_pricing — same convention as InventoryItems
            $serial->has_custom_pricing = (
                $serial->supplier_cost_price    !== null
                || $serial->total_shipping_cost !== null
                || $serial->ura_taxes_applied   !== null
                || $serial->additional_expenses !== null
                || $serial->grand_total_cost_price !== null
                || $serial->selling_price        !== null
                || $serial->discount_selling_price !== null
            );

            // pricing_source
            if (! $serial->pricing_source) {
                $serial->pricing_source = $serial->has_custom_pricing
                    ? 'serial'
                    : ($serial->resolveInventoryItem()
                        ? 'item'
                        : 'variant');
            }
        });
    }

    // ─── Relationships ──────────────────────────────────────────

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseReceipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceipt::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceiptItem::class, 'batch_id');
    }

    public function soldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Accessors ──────────────────────────────────────────────

    public function getLocationNameAttribute()
    {
        return $this->location ? $this->location->name : 'N/A';
    }

    public function getDepartmentNameAttribute()
    {
        return $this->department ? $this->department->name : 'N/A';
    }

    public function getStatusLabelAttribute()
    {
        return [
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_SOLD      => 'Sold',
            self::STATUS_RESERVED  => 'Reserved',
            self::STATUS_RETURNED  => 'Returned',
            self::STATUS_LOST      => 'Lost',
            self::STATUS_DAMAGED   => 'Damaged',
        ][$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        return [
            self::STATUS_AVAILABLE => 'success',
            self::STATUS_SOLD      => 'danger',
            self::STATUS_RESERVED  => 'warning',
            self::STATUS_RETURNED  => 'info',
            self::STATUS_LOST      => 'secondary',
            self::STATUS_DAMAGED   => 'dark',
        ][$this->status] ?? 'secondary';
    }

    // ─── Scopes ─────────────────────────────────────────────────

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }
    public function scopeSold($query)
    {
        return $query->where('status', self::STATUS_SOLD);
    }
    public function scopeByVariant($query, $variantId)
    {
        return $query->where('variant_id', $variantId);
    }
    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // ─── Helpers ────────────────────────────────────────────────

    public static function generateSerialNumber($variantId, $prefix = null)
    {
        $variant = ProductVariant::find($variantId);
        $prefix  = $prefix ?? ($variant ? strtoupper(substr($variant->sku, 0, 4)) : 'SN');
        $year    = date('Y');
        $random  = strtoupper(Str::random(6));
        $unique  = uniqid();

        return "{$prefix}-{$year}-{$random}-{$unique}";
    }

            /**
     * Sum of the four cost components. Returns the item's own
     * total when overrides are set; otherwise the caller should
     * fall back to `effective_grand_total_cost_price`.
     */
    public function calculateGrandTotalCostPrice(): float
    {
        return (float) ($this->supplier_cost_price    ?? 0)
             + (float) ($this->total_shipping_cost    ?? 0)
             + (float) ($this->ura_taxes_applied      ?? 0)
             + (float) ($this->additional_expenses    ?? 0);
    }

    /**
     * Accessor used by getSerials() and the modal to expose the
     * profit-per-unit on this serial.
     */
    public function getEffectiveProfitPerUnitAttribute(): float
    {
        return $this->effective_discount_selling_price
             - $this->effective_grand_total_cost_price;
    }

    /**
     * Accessor for margin as a percentage.
     */
    public function getEffectiveProfitMarginAttribute(): float
    {
        $net = $this->effective_discount_selling_price;
        if ($net <= 0) return 0.0;
        return ($this->effective_profit_per_unit / $net) * 100;
    }
}