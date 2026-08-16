<?php
// app/Models/ProductionOrderOutput.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderOutput extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_order_id',
        'product_variant_id',
        'batch_number',
        'planned_quantity',
        'actual_quantity',
        'defective_quantity',
        'unit',
        'production_cost',
        'selling_price',
        'total_cost',
        'inventory_strategy',
        'quality_status',
        'notes',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:4',
        'actual_quantity' => 'decimal:4',
        'defective_quantity' => 'decimal:4',
        'production_cost' => 'integer',
        'selling_price' => 'integer',
        'total_cost' => 'integer',
    ];

    const QUALITY_PENDING = 'pending';
    const QUALITY_APPROVED = 'approved';
    const QUALITY_REJECTED = 'rejected';

    // ============================================================
    // ACCESSORS & MUTATORS - Money Fields
    // ============================================================

    public function getProductionCostAttribute($value): float
    {
        return from_base_currency($value);
    }

    public function setProductionCostAttribute($value): void
    {
        $this->attributes['production_cost'] = to_base_currency($value);
    }

    public function getSellingPriceAttribute($value): float
    {
        return from_base_currency($value);
    }

    public function setSellingPriceAttribute($value): void
    {
        $this->attributes['selling_price'] = to_base_currency($value);
    }

    public function getTotalCostAttribute($value): float
    {
        return from_base_currency($value);
    }

    public function setTotalCostAttribute($value): void
    {
        $this->attributes['total_cost'] = to_base_currency($value);
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

    // ─── Helpers ──────────────────────────────────────────────────────────

    public function getTotalQuantityAttribute(): float
    {
        return $this->actual_quantity + $this->defective_quantity;
    }

    public function getYieldPercentageAttribute(): float
    {
        if ($this->planned_quantity > 0) {
            return ($this->actual_quantity / $this->planned_quantity) * 100;
        }
        return 0;
    }

    public function getDefectivePercentageAttribute(): float
    {
        if ($this->total_quantity > 0) {
            return ($this->defective_quantity / $this->total_quantity) * 100;
        }
        return 0;
    }

    public function getCostPerUnitAttribute(): float
    {
        if ($this->actual_quantity > 0) {
            return $this->production_cost / $this->actual_quantity;
        }
        return 0;
    }

    public function getProfitPerUnitAttribute(): float
    {
        if ($this->selling_price && $this->actual_quantity > 0) {
            return ($this->selling_price - $this->production_cost) / $this->actual_quantity;
        }
        return 0;
    }

    public function getTotalProfitAttribute(): float
    {
        if ($this->selling_price) {
            return $this->selling_price - $this->production_cost;
        }
        return 0;
    }

    public function getProfitMarginAttribute(): float
    {
        if ($this->selling_price && $this->selling_price > 0) {
            return (($this->selling_price - $this->production_cost) / $this->selling_price) * 100;
        }
        return 0;
    }

    public function isQualityApproved(): bool
    {
        return $this->quality_status === self::QUALITY_APPROVED;
    }

    public function isQualityRejected(): bool
    {
        return $this->quality_status === self::QUALITY_REJECTED;
    }

    public function hasDefects(): bool
    {
        return $this->defective_quantity > 0;
    }

    public function isFullyProduced(): bool
    {
        return $this->actual_quantity >= $this->planned_quantity;
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, $this->planned_quantity - $this->actual_quantity);
    }

    public function approveQuality(): self
    {
        $this->update(['quality_status' => self::QUALITY_APPROVED]);
        return $this;
    }

    public function rejectQuality(): self
    {
        $this->update(['quality_status' => self::QUALITY_REJECTED]);
        return $this;
    }

    public function updateActualQuantity(float $quantity, float $defective = 0): self
    {
        $this->update([
            'actual_quantity' => $quantity,
            'defective_quantity' => $defective,
        ]);
        return $this;
    }

    public function addProduction(float $quantity, float $defective = 0): self
    {
        $this->update([
            'actual_quantity' => $this->actual_quantity + $quantity,
            'defective_quantity' => $this->defective_quantity + $defective,
        ]);
        return $this;
    }

    // ─── Scopes ──────────────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('quality_status', self::QUALITY_APPROVED);
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

    public function scopeWithAvailableStock($query)
    {
        return $query->where('actual_quantity', '>', 0)
                     ->where('quality_status', self::QUALITY_APPROVED);
    }

    public function scopeWithDefects($query)
    {
        return $query->where('defective_quantity', '>', 0);
    }

    public function scopeCompleted($query)
    {
        return $query->where('actual_quantity', '>', 0);
    }

    public function scopeByStrategy($query, $strategy)
    {
        return $query->where('inventory_strategy', $strategy);
    }
}