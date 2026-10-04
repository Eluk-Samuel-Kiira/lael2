<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenant;

class InventoryAdjustments extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'quantity_before',
        'quantity_after',
        'reason',
        'notes',
        'inventory_id',
        'created_by',
        'tenant_id',

        // ★ Frozen pricing at the moment of adjustment
        'unit_cost_price',
        'unit_selling_price',
        'total_value_change',
    ];

    protected $casts = [
        'unit_cost_price'    => 'integer',
        'unit_selling_price' => 'integer',
        'total_value_change' => 'integer',
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

    public function getTotalValueChangeAttribute($value): ?float
    {
        return $value === null ? null : from_base_currency($value);
    }
    public function setTotalValueChangeAttribute($value): void
    {
        $this->attributes['total_value_change'] = $value === null ? null : to_base_currency($value);
    }

    /**
     * Auto-snapshot prices from the linked inventory item when
     * the adjustment is created and the caller didn't set them.
     */
    protected static function booted(): void
    {
        static::creating(function (self $adj) {
            if ($adj->inventory_id && (
                $adj->unit_cost_price === null
                || $adj->unit_selling_price === null
            )) {
                $item = $adj->InventoryItems ?? InventoryItems::find($adj->inventory_id);
                if ($item) {
                    if ($adj->unit_cost_price === null) {
                        $adj->unit_cost_price = $item->effective_grand_total_cost_price;
                    }
                    if ($adj->unit_selling_price === null) {
                        $adj->unit_selling_price = $item->effective_discount_selling_price;
                    }
                }
            }

            // Compute total_value_change from delta × cost unless given
            if ($adj->total_value_change === null) {
                $delta = (float) $adj->quantity_after - (float) $adj->quantity_before;
                $unitCost = (float) ($adj->unit_cost_price ?? 0);
                $adj->total_value_change = $delta * $unitCost;
            }
        });
    }

    public function InventoryItems()
    {
        return $this->belongsTo(InventoryItems::class, 'inventory_id', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}