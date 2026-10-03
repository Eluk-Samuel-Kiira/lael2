<?php
// app/Models/Recipe.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo; 
use Illuminate\Database\Eloquent\Relations\HasMany; 
use App\Traits\HasTenant;


class Recipe extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'product_id',
        'unit_cost',
        'unit_selling_price',
        'last_costed_at',
        'tenant_id',
    ];

    protected $casts = [
        'unit_cost'         => 'integer',
        'unit_selling_price'=> 'integer',
        'last_costed_at'    => 'datetime',
    ];

    public function getUnitCostAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }
    public function getUnitSellingPriceAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }
    public function setUnitCostAttribute($v): void
    {
        $this->attributes['unit_cost'] = $v === null ? null : to_base_currency($v);
    }
    public function setUnitSellingPriceAttribute($v): void
    {
        $this->attributes['unit_selling_price'] = $v === null ? null : to_base_currency($v);
    }

    /**
     * Recompute and cache the recipe's unit cost from its ingredients.
     */
    public function recalculateCost(): self
    {
        $total = $this->ingredients->sum(function (RecipeIngredient $i) {
            return (float) ($i->total_cost ?? ($i->unit_cost * $i->quantity_required));
        });

        $this->unit_cost      = $total;
        $this->last_costed_at = now();
        $this->save();

        return $this;
    }

    /**
     * Get the product that owns this recipe
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the ingredients for this recipe
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class, 'recipe_id');
    }

    /**
     * Get all production orders associated with products using this recipe
     */
    public function productionOrders()
    {
        return $this->hasManyThrough(
            ProductionOrder::class,
            Product::class,
            'id', // products.id
            'output_product_variant_id', // production_orders.output_product_variant_id
            'product_id', // recipes.product_id
            'id' // products.id
        );
    }
}