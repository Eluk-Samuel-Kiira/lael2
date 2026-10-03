<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenant;

class InventoryTransactions extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'quantity',
        'reference_id',
        'reference_type',
        'type',
        'notes',
        'inventory_id',
        'created_by',
        'tenant_id',

        // ★ Frozen pricing at the moment of the transaction
        'unit_cost_price',
        'unit_selling_price',
        'total_cost_value',
        'total_selling_value',
    ];

    protected $casts = [
        'unit_cost_price'     => 'integer',
        'unit_selling_price'  => 'integer',
        'total_cost_value'    => 'integer',
        'total_selling_value' => 'integer',
    ];

    public function getUnitCostPriceAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }
    public function setUnitCostPriceAttribute($value): void
    {
        $this->attributes['unit_cost_price'] = $value === null ? null : to_base_currency($value);
    }

    public function getUnitSellingPriceAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }
    public function setUnitSellingPriceAttribute($value): void
    {
        $this->attributes['unit_selling_price'] = $value === null ? null : to_base_currency($value);
    }

    public function getTotalCostValueAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }
    public function setTotalCostValueAttribute($value): void
    {
        $this->attributes['total_cost_value'] = $value === null ? null : to_base_currency($value);
    }

    public function getTotalSellingValueAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }
    public function setTotalSellingValueAttribute($value): void
    {
        $this->attributes['total_selling_value'] = $value === null ? null : to_base_currency($value);
    }

    /**
     * Convenience accessor — gross profit frozen on this transaction.
     */
    public function getGrossProfitAttribute(): float
    {
        return (float) ($this->total_selling_value ?? 0)
             - (float) ($this->total_cost_value ?? 0);
    }

    protected static function booted(): void
    {
        static::creating(function (self $txn) {
            if ($txn->inventory_id && (
                $txn->unit_cost_price === null
                || $txn->unit_selling_price === null
            )) {
                $item = $txn->InventoryItems ?? InventoryItems::find($txn->inventory_id);
                if ($item) {
                    if ($txn->unit_cost_price === null) {
                        $txn->unit_cost_price = $item->effective_grand_total_cost_price;
                    }
                    if ($txn->unit_selling_price === null) {
                        $txn->unit_selling_price = $item->effective_discount_selling_price;
                    }
                }
            }

            $qty = (float) $txn->quantity;

            if ($txn->total_cost_value === null) {
                $txn->total_cost_value = $qty * (float) ($txn->unit_cost_price ?? 0);
            }
            if ($txn->total_selling_value === null) {
                $txn->total_selling_value = $qty * (float) ($txn->unit_selling_price ?? 0);
            }
        });
    }

    public function InventoryItems()
    {
        return $this->belongsTo(InventoryItems::class, 'inventory_id', 'id');
    }
}