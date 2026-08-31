<?php
// app/Models/ProductionOrderInput.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasTenant;


class ProductionOrderInput extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'production_order_id',
        'product_variant_id',
        'purchase_receipt_item_id',
        'inventory_item_id',
        'planned_quantity',
        'actual_quantity',
        'waste_quantity',
        'unit',
        'estimated_cost',
        'actual_cost',
        'quality_status',
        'quality_notes',
        'tenant_id',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:4',
        'actual_quantity' => 'decimal:4',
        'waste_quantity' => 'decimal:4',
        'estimated_cost' => 'integer',
        'actual_cost' => 'integer',
    ];

    const QUALITY_PENDING = 'pending';
    const QUALITY_ACCEPTED = 'accepted';
    const QUALITY_REJECTED = 'rejected';

    // ============================================================
    // ACCESSORS & MUTATORS - Money Fields
    // ============================================================

    public function getEstimatedCostAttribute($value): float
    {
        return from_base_currency($value);
    }

    public function setEstimatedCostAttribute($value): void
    {
        $this->attributes['estimated_cost'] = to_base_currency($value);
    }

    public function getActualCostAttribute($value): float
    {
        return from_base_currency($value);
    }

    public function setActualCostAttribute($value): void
    {
        $this->attributes['actual_cost'] = to_base_currency($value);
    }

    // ─── Relationships ──────────────────────────────────────────────────

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function purchaseReceiptItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceiptItem::class, 'purchase_receipt_item_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItems::class, 'inventory_item_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    public function getTotalQuantityAttribute(): float
    {
        return $this->actual_quantity + $this->waste_quantity;
    }

    public function getYieldPercentageAttribute(): float
    {
        if ($this->planned_quantity > 0) {
            return ($this->actual_quantity / $this->planned_quantity) * 100;
        }
        return 0;
    }

    public function getWastePercentageAttribute(): float
    {
        if ($this->planned_quantity > 0) {
            return ($this->waste_quantity / $this->planned_quantity) * 100;
        }
        return 0;
    }

    public function getCostPerUnitAttribute(): float
    {
        if ($this->actual_quantity > 0) {
            return $this->actual_cost / $this->actual_quantity;
        }
        return 0;
    }

    public function isQualityAccepted(): bool
    {
        return $this->quality_status === self::QUALITY_ACCEPTED;
    }

    public function isQualityRejected(): bool
    {
        return $this->quality_status === self::QUALITY_REJECTED;
    }

    public function acceptQuality(): self
    {
        $this->update(['quality_status' => self::QUALITY_ACCEPTED]);
        return $this;
    }

    public function rejectQuality(?string $notes = null): self
    {
        $this->update([
            'quality_status' => self::QUALITY_REJECTED,
            'quality_notes' => $notes ?? $this->quality_notes,
        ]);
        return $this;
    }

    // ─── Scopes ──────────────────────────────────────────────────────────

    public function scopeAccepted($query)
    {
        return $query->where('quality_status', self::QUALITY_ACCEPTED);
    }

    public function scopeRejected($query)
    {
        return $query->where('quality_status', self::QUALITY_REJECTED);
    }

    public function scopePending($query)
    {
        return $query->where('quality_status', self::QUALITY_PENDING);
    }

    public function scopeByVariant($query, $variantId)
    {
        return $query->where('product_variant_id', $variantId);
    }
}