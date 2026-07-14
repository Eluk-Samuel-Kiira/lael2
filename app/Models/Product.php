<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'tenant_id',
        'category_id',
        'sku',
        'name',
        'description',
        'image_url',
        'type',
        'is_taxable',
        'is_active',
        'slug',
        'created_by',
    ];

    protected $casts = [
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }


    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id', 'id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id', 'id')->latest();
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function productCreater()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_product', 'product_id', 'department_id');
    }

    public function taxes()
    {
        return $this->belongsToMany(Tax::class, 'tax_product', 'product_id', 'tax_id');
    }

    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'promotion_products', 'product_id', 'promotion_id');
    }


    public function locations()
    {
        return $this->belongsToMany(Location::class, 'location_product', 'product_id', 'location_id');
    }

    public function resolvedInventoryStrategy(): string
    {
        return $this->inventory_strategy
            ?? $this->department?->default_inventory_strategy
            ?? 'quantity'; // tenant/global fallback
    }

    public function recipe(): HasOne
    {
        return $this->hasOne(Recipe::class);
    }

    public function productionOrders(): HasMany
    {
        return $this->hasManyThrough(
            ProductionOrder::class,
            ProductVariant::class,
            'product_id', // product_variants.product_id
            'output_product_variant_id', // production_orders.output_product_variant_id
            'id', // products.id
            'id' // product_variants.id
        );
    }

    // This single method is what your POS/inventory code calls everywhere instead of hardcoding logic — handleSingleShopInventory() becomes a dispatcher:
    // match ($variant->product->resolvedInventoryStrategy()) {
    //     'quantity' => $this->depleteQuantityOnly($variant, $item, $order),
    //     'batch'    => $this->depleteFIFOBatch($variant, $item, $order),
    //     'serial'   => $this->depleteSerialUnit($variant, $item, $order),
    //     'recipe'   => $this->depleteRecipeIngredients($variant, $item, $order),
    // };


}
