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
    ];

        // Accessor for discount_value - format as currency only when discount_type is 'fixed'
    public function getDiscountValueAttribute($value)
    {
        if ($this->discount_type === 'fixed_amount') {
            return formatCurrency($value);
        }
        
        return $value; // Return raw value for percentage and other types
    }

    // Mutator for discount_value - convert to USD only when discount_type is 'fixed'
    public function setDiscountValueAttribute($value)
    {
        if ($this->discount_type === 'fixed_amount') {
            $this->attributes['discount_value'] = toUSD($value);
        } else {
            $this->attributes['discount_value'] = $value; // Store raw value for percentages
        }
    }

    // Relationships
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
