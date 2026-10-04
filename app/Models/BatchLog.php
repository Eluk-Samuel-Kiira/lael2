<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenant;

class BatchLog extends Model
{
    use HasFactory, HasTenant;

    const TYPE_RECEIVED    = 'received';
    const TYPE_DEPLETED    = 'depleted';
    const TYPE_ADJUSTED    = 'adjusted';
    const TYPE_TRANSFERRED = 'transferred';
    const TYPE_EXPIRED     = 'expired';
    const TYPE_ASSIGNED    = 'assigned';
    const TYPE_UNASSIGNED  = 'unassigned';
    const TYPE_PRODUCED    = 'produced';

    protected $fillable = [
        // Batch reference
        'batch_id',
        'batch_number',

        // Product details
        'variant_id',
        'variant_name',
        'variant_sku',

        // Event details
        'type',
        'quantity_change',
        'quantity_before',
        'quantity_after',

        // ─── Cost tracking (base currency, integer storage) ───
        'unit_cost',
        'total_cost',

        // ★ Selling side — frozen at event time
        'unit_selling_price',
        'total_selling_value',
        'gross_profit',
        'pricing_source',

        // Reference links
        'order_id',
        'order_number',
        'purchase_order_id',
        'purchase_order_number',
        'purchase_receipt_id',
        'supplier_id',
        'supplier_name',

        // Tenant and location
        'tenant_id',
        'location_id',
        'department_id',

        // Expiry and dates
        'expiry_date',
        'event_date',

        // User who performed the action
        'performed_by',

        // Additional metadata
        'metadata',

        'production_order_id',
        'production_order_input_id',
        'production_order_output_id',
    ];

    protected $casts = [
        'quantity_change' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after'  => 'integer',

        // ★ These are now stored as base-currency integers,
        //   matching ProductVariant / InventoryItems / Transaction models.
        'unit_cost'           => 'integer',
        'total_cost'          => 'integer',
        'unit_selling_price'  => 'integer',
        'total_selling_value' => 'integer',
        'gross_profit'        => 'integer',

        'expiry_date' => 'date',
        'event_date'  => 'datetime',
        'metadata'    => 'array',
    ];

    // ─── Money accessors (base → display currency) ─────────────

    public function getUnitCostAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }

    public function getTotalCostAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }

    public function getUnitSellingPriceAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }

    public function getTotalSellingValueAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }

    public function getGrossProfitAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }

    // ─── Money mutators (display → base currency) ──────────────

    public function setUnitCostAttribute($value): void
    {
        $this->attributes['unit_cost'] = $value === null
            ? null
            : to_base_currency($value);
    }

    public function setTotalCostAttribute($value): void
    {
        $this->attributes['total_cost'] = $value === null
            ? null
            : to_base_currency($value);
    }

    public function setUnitSellingPriceAttribute($value): void
    {
        $this->attributes['unit_selling_price'] = $value === null
            ? null
            : to_base_currency($value);
    }

    public function setTotalSellingValueAttribute($value): void
    {
        $this->attributes['total_selling_value'] = $value === null
            ? null
            : to_base_currency($value);
    }

    public function setGrossProfitAttribute($value): void
    {
        $this->attributes['gross_profit'] = $value === null
            ? null
            : to_base_currency($value);
    }

    // ─── Auto-snapshot pricing at write time ───────────────────

    /**
     * When a new log is created without pricing, snapshot the current
     * variant pricing so historical reports stay truthful even after
     * the variant is later repriced.
     *
     * Prefers an InventoryItems-level override when we can resolve the
     * specific item (via location + department + variant); falls back
     * to variant pricing otherwise.
     */
    protected static function booted(): void
    {
        static::creating(function (self $log) {
            // ─── Resolve unit cost ──────────────────────────────
            if ($log->unit_cost === null) {
                [$cost, $source] = self::resolveUnitCost($log);
                $log->unit_cost      = $cost;
                $log->pricing_source = $log->pricing_source ?: $source;
            } elseif (! $log->pricing_source) {
                $log->pricing_source = 'log';
            }

            // ─── Resolve unit selling price ─────────────────────
            if ($log->unit_selling_price === null) {
                $log->unit_selling_price = self::resolveUnitSellingPrice($log);
            }

            // ─── Compute totals from change × unit price ────────
            $change = (float) $log->quantity_change;

            if ($log->total_cost === null) {
                $log->total_cost = $change * (float) ($log->unit_cost ?? 0);
            }
            if ($log->total_selling_value === null) {
                $log->total_selling_value = $change * (float) ($log->unit_selling_price ?? 0);
            }
            if ($log->gross_profit === null) {
                $log->gross_profit = (float) $log->total_selling_value
                                   - (float) $log->total_cost;
            }
        });
    }

    /**
     * Resolve unit cost — prefers an inventory-item override at the
     * specific location/department; falls back to the variant.
     *
     * @return array{0: float, 1: string}  [cost, pricing_source]
     */
    protected static function resolveUnitCost(self $log): array
    {
        // Try inventory item override first
        if ($log->variant_id && ($log->location_id || $log->department_id)) {
            $item = InventoryItems::query()
                ->where('tenant_id', $log->tenant_id)
                ->where('variant_id', $log->variant_id)
                ->when($log->location_id,   fn($q) => $q->where('location_id', $log->location_id))
                ->when($log->department_id, fn($q) => $q->where('department_id', $log->department_id))
                ->first();

            if ($item && (float) ($item->grand_total_cost_price ?? 0) > 0) {
                return [(float) $item->grand_total_cost_price, 'item'];
            }
        }

        // Fall back to variant
        $variant = $log->variant
            ?? ($log->variant_id ? ProductVariant::find($log->variant_id) : null);

        if (! $variant) {
            return [0, 'system'];
        }

        $grand = (float) ($variant->grand_total_cost_price ?? 0);
        if ($grand <= 0) {
            $grand = (float) ($variant->supplier_cost_price ?? 0)
                   + (float) ($variant->total_shipping_cost ?? 0)
                   + (float) ($variant->ura_taxes_applied   ?? 0)
                   + (float) ($variant->additional_expenses ?? 0);
        }

        return [$grand, 'variant'];
    }

    /**
     * Resolve unit selling price — mirrors resolveUnitCost().
     */
    protected static function resolveUnitSellingPrice(self $log): float
    {
        if ($log->variant_id && ($log->location_id || $log->department_id)) {
            $item = InventoryItems::query()
                ->where('tenant_id', $log->tenant_id)
                ->where('variant_id', $log->variant_id)
                ->when($log->location_id,   fn($q) => $q->where('location_id', $log->location_id))
                ->when($log->department_id, fn($q) => $q->where('department_id', $log->department_id))
                ->first();

            if ($item) {
                $price = (float) ($item->discount_selling_price
                    ?? $item->selling_price
                    ?? 0);
                if ($price > 0) return $price;
            }
        }

        $variant = $log->variant
            ?? ($log->variant_id ? ProductVariant::find($log->variant_id) : null);

        if (! $variant) return 0;

        return (float) ($variant->discount_selling_price
            ?? $variant->selling_price
            ?? 0);
    }

    // ─── Relationships ─────────────────────────────────────────

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function batch()
    {
        return $this->belongsTo(PurchaseReceiptItem::class, 'batch_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseReceipt()
    {
        return $this->belongsTo(PurchaseReceipt::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // ─── Scopes ────────────────────────────────────────────────

    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeReceived($query)
    {
        return $query->where('type', self::TYPE_RECEIVED);
    }

    public function scopeDepleted($query)
    {
        return $query->where('type', self::TYPE_DEPLETED);
    }

    public function scopeByVariant($query, $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeByBatchNumber($query, $batchNumber)
    {
        return $query->where('batch_number', $batchNumber);
    }

    public function scopeByLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('event_date', [$startDate, $endDate]);
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
    }

    // ─── Attribute helpers ─────────────────────────────────────

    public function getTypeLabelAttribute()
    {
        return [
            self::TYPE_RECEIVED   => 'Received',
            self::TYPE_DEPLETED   => 'Depleted',
            self::TYPE_ADJUSTED   => 'Adjusted',
            self::TYPE_TRANSFERRED=> 'Transferred',
            self::TYPE_ASSIGNED   => 'Assigned',
            self::TYPE_UNASSIGNED => 'Unassigned',
            self::TYPE_EXPIRED    => 'Expired',
            self::TYPE_PRODUCED   => 'Produced',
        ][$this->type] ?? ucfirst($this->type);
    }

    public function getTypeColorAttribute()
    {
        return [
            self::TYPE_RECEIVED   => 'success',
            self::TYPE_DEPLETED   => 'danger',
            self::TYPE_ADJUSTED   => 'warning',
            self::TYPE_TRANSFERRED=> 'info',
            self::TYPE_ASSIGNED   => 'primary',
            self::TYPE_UNASSIGNED => 'secondary',
            self::TYPE_EXPIRED    => 'secondary',
            self::TYPE_PRODUCED   => 'info',
        ][$this->type] ?? 'primary';
    }

    public function getTypeIconAttribute()
    {
        return [
            self::TYPE_RECEIVED   => 'fa-arrow-down',
            self::TYPE_DEPLETED   => 'fa-arrow-up',
            self::TYPE_ADJUSTED   => 'fa-pencil',
            self::TYPE_TRANSFERRED=> 'fa-arrow-right',
            self::TYPE_EXPIRED    => 'fa-clock',
            self::TYPE_PRODUCED   => 'fa-industry',
        ][$this->type] ?? 'fa-circle';
    }

    public function getLocationNameAttribute()
    {
        return $this->location ? $this->location->name : 'N/A';
    }

    public function getDepartmentNameAttribute()
    {
        return $this->department ? $this->department->name : 'N/A';
    }

    public function getFormattedExpiryDateAttribute()
    {
        return $this->expiry_date ? $this->expiry_date->format('Y-m-d') : 'N/A';
    }

    public function getDaysToExpiryAttribute()
    {
        if (! $this->expiry_date) {
            return null;
        }
        return now()->diffInDays($this->expiry_date, false);
    }

    public function getIsExpiredAttribute()
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getIsExpiringSoonAttribute()
    {
        if (! $this->expiry_date) {
            return false;
        }
        $days = now()->diffInDays($this->expiry_date, false);
        return $days >= 0 && $days <= 30;
    }
}