<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo; 
use App\Traits\HasTenant;


class RecipeIngredient extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'recipe_id',
        'ingredient_variant_id',
        'quantity_required',

        // ★ Cost + price snapshot
        'unit_cost',
        'total_cost',
        'unit_selling_price',
        'pricing_source',

        'unit_id',
        'tenant_id',
    ];

    protected $casts = [
        'quantity_required' => 'decimal:4',
        'unit_cost'         => 'integer',
        'total_cost'        => 'integer',
        'unit_selling_price'=> 'integer',
    ];

    // Accessors
    public function getUnitCostAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }
    public function getTotalCostAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }
    public function getUnitSellingPriceAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }

    // Mutators
    public function setUnitCostAttribute($v): void
    {
        $this->attributes['unit_cost'] = $v === null ? null : to_base_currency($v);
    }
    public function setTotalCostAttribute($v): void
    {
        $this->attributes['total_cost'] = $v === null ? null : to_base_currency($v);
    }
    public function setUnitSellingPriceAttribute($v): void
    {
        $this->attributes['unit_selling_price'] = $v === null ? null : to_base_currency($v);
    }

    /**
     * Auto-compute total_cost from unit_cost × quantity_required on save.
     */
    protected static function booted(): void
    {
        static::saving(function (self $row) {
            if ($row->isDirty('unit_cost') || $row->isDirty('quantity_required')) {
                if (! $row->isDirty('total_cost')) {
                    $row->total_cost = (float) $row->unit_cost * (float) $row->quantity_required;
                }
            }
        });
    }

    /**
     * Populate cost and selling price from the ingredient variant,
     * preferring the inventory item when a location/department is given.
     */
    public function syncPricingFromVariant(?InventoryItems $inventory = null): self
    {
        $variant = $this->ingredientVariant;
        if (! $variant) return $this;

        $cost = 0.0;
        $source = 'variant';

        if ($inventory && (float) ($inventory->grand_total_cost_price ?? 0) > 0) {
            $cost   = (float) $inventory->grand_total_cost_price;
            $source = 'item';
        } else {
            $cost = (float) ($variant->grand_total_cost_price ?? 0)
                ?: (
                    (float) ($variant->supplier_cost_price ?? 0)
                    + (float) ($variant->total_shipping_cost ?? 0)
                    + (float) ($variant->ura_taxes_applied   ?? 0)
                    + (float) ($variant->additional_expenses ?? 0)
                );
        }

        $this->unit_cost          = $cost;
        $this->unit_selling_price = (float) (
            $variant->discount_selling_price ?? $variant->selling_price ?? 0
        );
        $this->pricing_source     = $source;

        return $this;
    }


    /**
     * Get the recipe this ingredient belongs to
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class, 'recipe_id');
    }

    /**
     * Get the product variant for this ingredient
     */
    public function ingredientVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'ingredient_variant_id');
    }

    /**
     * Get the unit of measure for this ingredient
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }
}