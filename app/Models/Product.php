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


}
