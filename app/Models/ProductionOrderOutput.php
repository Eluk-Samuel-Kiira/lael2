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
        'batch_no',
        'planned_quantity',
        'actual_quantity',
        'defective_quantity',
        'unit',
        'packaging_type',
        'packaging_weight',
        'production_cost',
        'selling_price',
        'quality_status',
        'notes',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:4',
        'actual_quantity' => 'decimal:4',
        'defective_quantity' => 'decimal:4',
        'packaging_weight' => 'decimal:4',
        'production_cost' => 'integer',
        'selling_price' => 'integer',
    ];

    // Status Constants
    const QUALITY_PENDING = 'pending';
    const QUALITY_APPROVED = 'approved';
    const QUALITY_REJECTED = 'rejected';

    /**
     * Get the production order this output belongs to
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    /**
     * Get the product variant being produced
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Calculate total quantity (actual + defective)
     */
    public function getTotalQuantityAttribute(): float
    {
        return $this->actual_quantity + $this->defective_quantity;
    }

    /**
     * Calculate yield percentage (actual / planned)
     */
    public function getYieldPercentageAttribute(): float
    {
        if ($this->planned_quantity > 0) {
            return ($this->actual_quantity / $this->planned_quantity) * 100;
        }
        return 0;
    }

    /**
     * Calculate defective percentage
     */
    public function getDefectivePercentageAttribute(): float
    {
        if ($this->total_quantity > 0) {
            return ($this->defective_quantity / $this->total_quantity) * 100;
        }
        return 0;
    }

    /**
     * Get the cost per unit
     */
    public function getCostPerUnitAttribute(): float
    {
        if ($this->actual_quantity > 0) {
            return $this->production_cost / $this->actual_quantity;
        }
        return 0;
    }

    /**
     * Get the production cost in dollars
     */
    public function getProductionCostInDollarsAttribute(): float
    {
        return $this->production_cost / 100;
    }

    /**
     * Get the selling price in dollars
     */
    public function getSellingPriceInDollarsAttribute(): float
    {
        return $this->selling_price ? $this->selling_price / 100 : 0;
    }

    /**
     * Calculate profit per unit
     */
    public function getProfitPerUnitAttribute(): float
    {
        if ($this->selling_price && $this->actual_quantity > 0) {
            return ($this->selling_price - ($this->production_cost / $this->actual_quantity)) / 100;
        }
        return 0;
    }

    /**
     * Calculate total profit
     */
    public function getTotalProfitAttribute(): float
    {
        if ($this->selling_price) {
            return ($this->selling_price - $this->production_cost) / 100;
        }
        return 0;
    }

    /**
     * Calculate profit margin percentage
     */
    public function getProfitMarginAttribute(): float
    {
        if ($this->selling_price && $this->selling_price > 0) {
            return (($this->selling_price - $this->production_cost) / $this->selling_price) * 100;
        }
        return 0;
    }

    /**
     * Generate batch number automatically
     */
    public function generateBatchNumber(): string
    {
        return $this->productionOrder->production_number . '-' . $this->product_variant_id;
    }

    /**
     * Check if quality is approved
     */
    public function isQualityApproved(): bool
    {
        return $this->quality_status === self::QUALITY_APPROVED;
    }

    /**
     * Check if quality is rejected
     */
    public function isQualityRejected(): bool
    {
        return $this->quality_status === self::QUALITY_REJECTED;
    }

    /**
     * Check if there are defects
     */
    public function hasDefects(): bool
    {
        return $this->defective_quantity > 0;
    }

    /**
     * Check if fully produced (actual >= planned)
     */
    public function isFullyProduced(): bool
    {
        return $this->actual_quantity >= $this->planned_quantity;
    }

    /**
     * Get remaining quantity to produce
     */
    public function getRemainingQuantityAttribute(): float
    {
        return max(0, $this->planned_quantity - $this->actual_quantity);
    }

    /**
     * Approve the quality
     */
    public function approveQuality(): self
    {
        $this->update(['quality_status' => self::QUALITY_APPROVED]);
        return $this;
    }

    /**
     * Reject the quality
     */
    public function rejectQuality(): self
    {
        $this->update(['quality_status' => self::QUALITY_REJECTED]);
        return $this;
    }

    /**
     * Update actual quantity
     */
    public function updateActualQuantity(float $quantity, float $defective = 0): self
    {
        $this->update([
            'actual_quantity' => $quantity,
            'defective_quantity' => $defective,
        ]);
        return $this;
    }

    /**
     * Add production to this output
     */
    public function addProduction(float $quantity, float $defective = 0): self
    {
        $this->update([
            'actual_quantity' => $this->actual_quantity + $quantity,
            'defective_quantity' => $this->defective_quantity + $defective,
        ]);
        return $this;
    }

    // Scopes
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

    public function scopeByBatch($query, $batchNo)
    {
        return $query->where('batch_no', $batchNo);
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
}