<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SingleShopInventoryLog extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'metadata' => 'array',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'quantity_change' => 'integer',
    ];

    /**
     * Relationships
     */
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
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

    /**
     * Scopes
     */
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

    /**
     * Helper methods
     */
    public function isSale()
    {
        return $this->reason === 'pos_sale';
    }

    public function isReturn()
    {
        return $this->reason === 'return';
    }

    public function isAdjustment()
    {
        return $this->reason === 'stock_adjustment';
    }

    /**
     * Get current stock level for a variant
     */
    public static function getCurrentStock($variantId)
    {
        $latestLog = static::where('variant_id', $variantId)
            ->latest('created_at')
            ->first();

        return $latestLog ? $latestLog->quantity_after : 0;
    }

    /**
     * Get stock movement history for a variant
     */
    public static function getStockHistory($variantId, $days = 30)
    {
        return static::where('variant_id', $variantId)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }
}