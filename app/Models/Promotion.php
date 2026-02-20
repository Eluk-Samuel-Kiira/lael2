<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    /** @use HasFactory<\Database\Factories\PromotionFactory> */
    use HasFactory;
    
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'is_active',
        'max_uses',
        'max_uses_per_customer',
        'min_order_amount',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        // Money fields - stored as integers in DB
        'discount_value' => 'integer',
        'min_order_amount' => 'integer',
    ];

    /**
     * Accessors - Convert from stored integer to display float
     */
    public function getDiscountValueAttribute(?int $value): ?float
    {
        // If discount_type is percentage, value is stored as integer (e.g., 1500 = 15.00%)
        // If discount_type is fixed_amount, value is stored as currency in smallest unit
        return $this->discount_type === 'percentage' 
            ? (float) ($value / 100) // Convert percentage to decimal (1500 → 15.00)
            : from_base_currency($value); // Convert currency amount
    }

    public function getMinOrderAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    /**
     * Mutators - Convert from display float to stored integer
     */
    public function setDiscountValueAttribute($value): void
    {
        if ($this->discount_type === 'percentage') {
            // Convert percentage to integer (15.00% → 1500)
            $this->attributes['discount_value'] = (int) round((float) $value * 100);
        } else {
            // Fixed amount - convert currency to smallest unit
            $this->attributes['discount_value'] = to_base_currency($value);
        }
    }

    public function setMinOrderAmountAttribute($value): void
    {
        $this->attributes['min_order_amount'] = to_base_currency($value);
    }

    /**
     * Relationships
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function Promotioncreator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function products()
    {
        return $this->hasMany(PromotionProduct::class, 'promotion_id');
    }
}