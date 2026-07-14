<?php
// app/Models/ProductionOrderInput.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderInput extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_order_id',
        'product_variant_id',
        'batch_no',
        'purchase_order_item_id',
        'planned_quantity',
        'actual_quantity',
        'waste_quantity',
        'unit',
        'estimated_cost',
        'actual_cost',
        'quality_status',
        'quality_notes',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:4',
        'actual_quantity' => 'decimal:4',
        'waste_quantity' => 'decimal:4',
        'estimated_cost' => 'integer',
        'actual_cost' => 'integer',
    ];

    // Status Constants
    const QUALITY_PENDING = 'pending';
    const QUALITY_ACCEPTED = 'accepted';
    const QUALITY_REJECTED = 'rejected';

    /**
     * Get the production order this input belongs to
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    /**
     * Get the product variant being consumed
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the purchase order item this input came from
     */
    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    /**
     * Calculate total quantity (actual + waste)
     */
    public function getTotalQuantityAttribute(): float
    {
        return $this->actual_quantity + $this->waste_quantity;
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
     * Calculate waste percentage
     */
    public function getWastePercentageAttribute(): float
    {
        if ($this->planned_quantity > 0) {
            return ($this->waste_quantity / $this->planned_quantity) * 100;
        }
        return 0;
    }

    /**
     * Get the cost per unit
     */
    public function getCostPerUnitAttribute(): float
    {
        if ($this->actual_quantity > 0) {
            return $this->actual_cost / $this->actual_quantity;
        }
        return 0;
    }

    /**
     * Get the estimated cost in dollars
     */
    public function getEstimatedCostInDollarsAttribute(): float
    {
        return $this->estimated_cost / 100;
    }

    /**
     * Get the actual cost in dollars
     */
    public function getActualCostInDollarsAttribute(): float
    {
        return $this->actual_cost / 100;
    }

    /**
     * Check if quality is accepted
     */
    public function isQualityAccepted(): bool
    {
        return $this->quality_status === self::QUALITY_ACCEPTED;
    }

    /**
     * Check if quality is rejected
     */
    public function isQualityRejected(): bool
    {
        return $this->quality_status === self::QUALITY_REJECTED;
    }

    /**
     * Accept the quality
     */
    public function acceptQuality(): self
    {
        $this->update(['quality_status' => self::QUALITY_ACCEPTED]);
        return $this;
    }

    /**
     * Reject the quality
     */
    public function rejectQuality(string $notes = null): self
    {
        $this->update([
            'quality_status' => self::QUALITY_REJECTED,
            'quality_notes' => $notes ?? $this->quality_notes,
        ]);
        return $this;
    }

    // Scopes
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

    public function scopeByBatch($query, $batchNo)
    {
        return $query->where('batch_no', $batchNo);
    }
}