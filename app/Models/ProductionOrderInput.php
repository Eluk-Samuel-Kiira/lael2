<?php
// app/Models/ProductionOrderInput.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasTenant;
use App\Models\PurchaseReceiptItem;
use App\Models\SerialNumber;


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
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function purchaseReceiptItem()
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



    /**
     * Where does the availability for this input actually come from?
     * Returns one of: 'batch_specific', 'batch_fifo', 'serial', 'quantity'.
     */
    public function getAvailabilitySourceAttribute(): string
    {
        if ($this->purchase_receipt_item_id) {
            return 'batch_specific';
        }

        $variant  = $this->productVariant;
        $product  = $variant?->product;
        $strategy = $product?->resolvedInventoryStrategy() ?? 'quantity';

        return match ($strategy) {
            'batch'  => 'batch_fifo',
            'serial' => 'serial',
            default  => 'quantity',
        };
    }

    /**
     * The number the modal should display as "available".
     * Mirrors ProductionOrder::validateInputStock() so the UI never
     * disagrees with the backend.
     */
    public function getAvailableQuantityAttribute(): float
    {
        $variant = $this->productVariant;
        if (!$variant) return 0;

        // 1. Specific batch selected
        if ($this->purchase_receipt_item_id) {
            $batch = PurchaseReceiptItem::where('id', $this->purchase_receipt_item_id)
                ->where('tenant_id', $this->tenant_id)
                ->first();

            if (!$batch) return 0;

            return (float) ($batch->quantity_remaining ?? $batch->quantity_received ?? 0);
        }

        $product  = $variant->product;
        $strategy = $product?->resolvedInventoryStrategy() ?? 'quantity';

        // 2. Batch strategy, no specific batch → total across batches
        if ($strategy === 'batch') {
            return (float) PurchaseReceiptItem::query()
                ->join('purchase_receipts', 'purchase_receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
                ->join('purchase_orders', 'purchase_receipts.purchase_order_id', '=', 'purchase_orders.id')
                ->join('purchase_order_items', 'purchase_receipt_items.purchase_order_item_id', '=', 'purchase_order_items.id')
                ->where('purchase_orders.tenant_id', $this->tenant_id)
                ->where('purchase_order_items.product_variant_id', $variant->id)
                ->where(function ($q) {
                    $q->where('purchase_receipt_items.quantity_remaining', '>', 0)
                    ->orWhereNull('purchase_receipt_items.quantity_remaining');
                })
                ->sum('purchase_receipt_items.quantity_remaining');
        }

        // 3. Serial strategy → count available serials
        if ($strategy === 'serial') {
            return (float) SerialNumber::where('variant_id', $variant->id)
                ->where('status', SerialNumber::STATUS_AVAILABLE)
                ->where('tenant_id', $this->tenant_id)
                ->count();
        }

        // 4. Quantity strategy → overall
        return (float) ($variant->overal_quantity_at_hand ?? 0);
    }

    /**
     * Optional: a label describing where the number comes from,
     * so the UI can show "(batch ABC)" or "(FIFO)".
     */
    public function getAvailabilityLabelAttribute(): string
    {
        return match ($this->availability_source) {
            'batch_specific' => $this->purchaseReceiptItem?->batch_number
                                ? '(' . $this->purchaseReceiptItem->batch_number . ')'
                                : __('passwords.batch'),
            'batch_fifo'     => __('passwords.all_batches'),
            'serial'         => __('passwords.serial_numbers'),
            default          => '',
        };
    }
}