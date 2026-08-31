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
        'tenant_id',
    ];

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