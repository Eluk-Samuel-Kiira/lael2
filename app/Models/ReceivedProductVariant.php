<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivedProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'purchase_receipt_id',
        'purchase_order_item_id',
        'product_variant_id',
        'quantity_received',
        'unit_cost',
        'total_cost',
        'batch_number',
        'expiry_date',
        'notes',
        'inventory_quantity_before',
        'inventory_quantity_after',
        'received_by',
        'tenant_id',
    ];

    protected $casts = [
        'quantity_received' => 'integer',
        // Money fields - stored as integers in DB
        'unit_cost' => 'integer',
        'total_cost' => 'integer',
        'inventory_quantity_before' => 'integer',
        'inventory_quantity_after' => 'integer',
        'expiry_date' => 'date',
        'received_at' => 'datetime',
    ];

    /**
     * Accessors - Convert from stored integer to display float
     */
    public function getUnitCostAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getTotalCostAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    /**
     * Mutators - Convert from display float to stored integer
     */
    public function setUnitCostAttribute($value): void
    {
        $this->attributes['unit_cost'] = to_base_currency($value);
    }

    public function setTotalCostAttribute($value): void
    {
        $this->attributes['total_cost'] = to_base_currency($value);
    }




    /**
     * Relationships
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseReceipt()
    {
        return $this->belongsTo(PurchaseReceipt::class);
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scopes
     */
    public function scopeByPurchaseOrder($query, $purchaseOrderId)
    {
        return $query->where('purchase_order_id', $purchaseOrderId);
    }

    public function scopeByProductVariant($query, $productVariantId)
    {
        return $query->where('product_variant_id', $productVariantId);
    }

    public function scopeByBatch($query, $batchNumber)
    {
        return $query->where('batch_number', $batchNumber);
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
                    ->where('expiry_date', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    /**
     * Accessors
     */
    public function getIsExpiredAttribute()
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->expiry_date) {
            return null;
        }
        
        return now()->diffInDays($this->expiry_date, false);
    }

    public function getInventoryChangeAttribute()
    {
        return $this->inventory_quantity_after - $this->inventory_quantity_before;
    }

    
    /**
     * Get total quantity received for a purchase order
     */
    public static function getTotalReceivedForPurchaseOrder($purchaseOrderId)
    {
        return static::where('purchase_order_id', $purchaseOrderId)->sum('quantity_received');
    }

    /**
     * Get received items by batch number
     */
    public static function getByBatchNumber($batchNumber)
    {
        return static::where('batch_number', $batchNumber)->get();
}

    /**
     * Get received items summary by product variant
     */
    public static function getReceivedSummaryByVariant($productVariantId = null)
    {
        $query = static::query();
        
        if ($productVariantId) {
            $query->where('product_variant_id', $productVariantId);
        }
        
        return $query->selectRaw('
            product_variant_id,
            SUM(quantity_received) as total_quantity_received,
            AVG(unit_cost) as average_unit_cost,
            SUM(total_cost) as total_cost,
            COUNT(*) as receipt_count
        ')->groupBy('product_variant_id')->get();
    }

    /**
     * Get expiring items report
     */
    public static function getExpiringItemsReport($days = 30)
    {
        return static::with(['productVariant', 'purchaseOrder'])
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now())
            ->orderBy('expiry_date')
            ->get();
    }
}