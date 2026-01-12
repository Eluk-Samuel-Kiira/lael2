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
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'inventory_data' => 'array', // Automatically cast JSON to array
        'tax_data' => 'array', 
        'promotion_data' => 'array',
    ];

    // 👇 Accessors
    public function getUnitPriceAttribute($value) {
        return formatCurrency($value);
    }
    public function getDiscountAttribute($value) {
        return formatCurrency($value);
    }
    public function getTaxAmountAttribute($value) {
        return formatCurrency($value);
    }
    public function getTotalPriceAttribute($value) {
        return formatCurrency($value);
    }

        // 👇 Mutators - Convert to USD when WRITING to database
    public function setUnitPriceAttribute($value) {
        $this->attributes['unit_price'] = toUSD($value);
    }
    public function setDiscountAttribute($value) {
        $this->attributes['discount'] = toUSD($value);
    }
    public function setTaxAmountAttribute($value) {
        $this->attributes['tax_amount'] = toUSD($value);
    }
    public function setTotalPriceAttribute($value) {
        $this->attributes['total_price'] = toUSD($value);
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
