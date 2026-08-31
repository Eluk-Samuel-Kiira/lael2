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
        'unit_id',
        'tenant_id',
    ];

    protected $casts = [
        'quantity_required' => 'decimal:4',
    ];

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