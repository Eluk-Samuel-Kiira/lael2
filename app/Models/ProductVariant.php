<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariantFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'barcode',
        'price',
        'cost_price',
        'weight',
        'weight_unit',
        'image_url',
        'is_active',
        'created_by',
        'tenant_id',
        'overal_quantity_at_hand',
        'is_taxable',
    ];

    // 👇 Accessors to format currency automatically
    public function getPriceAttribute($value)
    {
        return formatCurrency($value);
    }

    public function getCostPriceAttribute($value)
    {
        return formatCurrency($value);
    }

    // 👇 MUTATORS - Convert to USD when WRITING to database
    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = toUSD($value);
    }

    public function setCostPriceAttribute($value)
    {
        $this->attributes['cost_price'] = toUSD($value);
    }



    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
    
    public function unitMeasure()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'weight_unit', 'id');
    }

    public function variantCreater()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    
    public function inventory()
    {   
        return $this->hasMany(InventoryItems::class, 'variant_id', 'id');
    }

    public function variantTaxes()
    {
        return $this->belongsToMany(Tax::class, 'variant_taxes', 'variant_id', 'tax_id')
                    ->withPivot(['created_by', 'tenant_id'])
                    ->withTimestamps();
    }

    public function Variantpromotions()
    {
        return $this->belongsToMany(Promotion::class, 'promotion_products', 'variant_id', 'promotion_id');
    }

}
