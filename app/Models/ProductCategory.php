<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'parent_category_id',
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
}
