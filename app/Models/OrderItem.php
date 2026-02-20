<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'item_name',
        'sku',
        'unit_price',
        'quantity',
        'discount',
        'tax_amount',
        'total_price',
        'inventory_data',
        'tax_data',
        'promotion_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // Money fields - stored as integers in DB
        'unit_price' => 'integer',
        'discount' => 'integer',
        'tax_amount' => 'integer',
        'total_price' => 'integer',
        'inventory_data' => 'array',
        'tax_data' => 'array',
        'promotion_data' => 'array',
    ];

    /**
     * Accessors - Convert from stored integer to display float
     */
    public function getUnitPriceAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getDiscountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getTaxAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getTotalPriceAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    /**
     * Mutators - Convert from display float to stored integer
     */
    public function setUnitPriceAttribute($value): void
    {
        $this->attributes['unit_price'] = to_base_currency($value);
    }

    public function setDiscountAttribute($value): void
    {
        $this->attributes['discount'] = to_base_currency($value);
    }

    public function setTaxAmountAttribute($value): void
    {
        $this->attributes['tax_amount'] = to_base_currency($value);
    }

    public function setTotalPriceAttribute($value): void
    {
        $this->attributes['total_price'] = to_base_currency($value);
    }

    /**
     * Get the order that owns the order item.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product that owns the order item.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant that owns the order item.
     */
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}