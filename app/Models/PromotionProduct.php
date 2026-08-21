<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenant;


class PromotionProduct extends Model
{
    /** @use HasFactory<\Database\Factories\PromotionProductFactory> */
    use HasFactory, HasTenant;

    protected $fillable = [
        'promotion_id',
        'product_id',
        'variant_id',
        'applies_to',
    ];

    // Relationships
    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    
}
