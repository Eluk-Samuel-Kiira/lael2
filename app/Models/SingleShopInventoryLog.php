<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SingleShopInventoryLog extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'variant_id',
        'order_id',
        'tenant_id',
        'created_by',
        'quantity_before',
        'quantity_after',
        'quantity_change',
        'reason',
        'notes',
        'source',
        'metadata',

        // ★ Frozen pricing at the moment of the movement
        'unit_cost_price',
        'unit_selling_price',
        'total_cost_value',
        'total_selling_value',
        'pricing_source',
    ];

    protected $casts = [
        'metadata'         => 'array',
        'quantity_before'  => 'integer',
        'quantity_after'   => 'integer',
        'quantity_change'  => 'integer',

        // Money fields stored as integers in DB (same convention as ProductVariant)
        'unit_cost_price'     => 'integer',
        'unit_selling_price'  => 'integer',
        'total_cost_value'    => 'integer',
        'total_selling_value' => 'integer',
    ];

    // ─── Money accessors (base → display currency) ─────────────

    public function getUnitCostPriceAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }

    public function getUnitSellingPriceAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }

    public function getTotalCostValueAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }

    public function getTotalSellingValueAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }

    // ─── Money mutators (display → base currency) ──────────────

    public function setUnitCostPriceAttribute($value): void
    {
        $this->attributes['unit_cost_price'] = $value === null
            ? null
            : to_base_currency($value);
    }

    public function setUnitSellingPriceAttribute($value): void
    {
        $this->attributes['unit_selling_price'] = $value === null
            ? null
            : to_base_currency($value);
    }

    public function setTotalCostValueAttribute($value): void
    {
        $this->attributes['total_cost_value'] = $value === null
            ? null
            : to_base_currency($value);
    }

    public function setTotalSellingValueAttribute($value): void
    {
        $this->attributes['total_selling_value'] = $value === null
            ? null
            : to_base_currency($value);
    }

    // ─── Derived values ────────────────────────────────────────

    /**
     * Gross profit frozen on this single movement log.
     */
    public function getGrossProfitAttribute(): float
    {
        return (float) ($this->total_selling_value ?? 0)
             - (float) ($this->total_cost_value ?? 0);
    }

    /**
     * Is this movement an outflow (negative quantity change)?
     */
    public function getIsOutflowAttribute(): bool
    {
        return (float) $this->quantity_change < 0;
    }

    /**
     * Is this movement an inflow (positive quantity change)?
     */
    public function getIsInflowAttribute(): bool
    {
        return (float) $this->quantity_change > 0;
    }

    // ─── Auto-snapshot on create ───────────────────────────────

    /**
     * When a new log is written without pricing, snapshot the current
     * variant pricing so historical reports stay truthful even after
     * the variant is later repriced.
     */
    protected static function booted(): void
    {
        static::creating(function (self $log) {
            $variant = $log->variant
                ?? ($log->variant_id ? ProductVariant::find($log->variant_id) : null);

            // ─── Resolve unit cost ──────────────────────────────
            if ($log->unit_cost_price === null) {
                if ($variant) {
                    $grand = (float) ($variant->grand_total_cost_price ?? 0);
                    if ($grand <= 0) {
                        $grand = (float) ($variant->supplier_cost_price ?? 0)
                               + (float) ($variant->total_shipping_cost ?? 0)
                               + (float) ($variant->ura_taxes_applied   ?? 0)
                               + (float) ($variant->additional_expenses ?? 0);
                    }
                    $log->unit_cost_price = $grand;
                    $log->pricing_source  = 'variant';
                } else {
                    $log->unit_cost_price = 0;
                    $log->pricing_source  = 'system';
                }
            } else {
                $log->pricing_source = $log->pricing_source ?: 'log';
            }

            // ─── Resolve unit selling price ─────────────────────
            if ($log->unit_selling_price === null && $variant) {
                $log->unit_selling_price = (float) (
                    $variant->discount_selling_price
                    ?? $variant->selling_price
                    ?? 0
                );
            }

            // ─── Compute totals from change × unit price ────────
            $change = (float) $log->quantity_change;

            if ($log->total_cost_value === null) {
                $log->total_cost_value = $change * (float) ($log->unit_cost_price ?? 0);
            }
            if ($log->total_selling_value === null) {
                $log->total_selling_value = $change * (float) ($log->unit_selling_price ?? 0);
            }
        });
    }

    // ─── Relationships ─────────────────────────────────────────

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ────────────────────────────────────────────────

    public function scopeSales($query)
    {
        return $query->where('reason', 'pos_sale');
    }

    public function scopeReturns($query)
    {
        return $query->where('reason', 'return');
    }

    public function scopeAdjustments($query)
    {
        return $query->where('reason', 'stock_adjustment');
    }

    public function scopeForVariant($query, $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    // ─── Helpers ───────────────────────────────────────────────

    public function isSale(): bool
    {
        return $this->reason === 'pos_sale';
    }

    public function isReturn(): bool
    {
        return $this->reason === 'return';
    }

    public function isAdjustment(): bool
    {
        return $this->reason === 'stock_adjustment';
    }

    /**
     * Current stock level for a variant.
     */
    public static function getCurrentStock($variantId)
    {
        $latestLog = static::where('variant_id', $variantId)
            ->latest('created_at')
            ->first();

        return $latestLog ? $latestLog->quantity_after : 0;
    }

    /**
     * Stock movement history for a variant.
     */
    public static function getStockHistory($variantId, $days = 30)
    {
        return static::where('variant_id', $variantId)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }
}