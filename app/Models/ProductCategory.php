<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductCategory extends Model
{
    use HasFactory;

    protected $table = 'product_categories';
    
    protected $fillable = [
        'tenant_id',
        'parent_category_id',  // ← This is the correct column name
        'name',
        'description',
        'image_url',
        'is_active',
        'slug',
        'created_by',
    ];

    public function productCategoryCreater()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function parentCategory()
    {
        return $this->belongsTo(Category::class, 'parent_category_id', 'id');
    }
    
    public function childCategories()
    {
        return $this->hasMany(ProductCategory::class, 'parent_category_id', 'id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id', 'id');
    }
}