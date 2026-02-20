<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderTax extends Model
{
    /** @use HasFactory<\Database\Factories\OrderTaxFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'tax_name',
        'tax_rate',
        'tax_amount',
        'is_compound',
        'created_by',
    ];

    protected $casts = [
        // Tax rate is a percentage, not a currency amount
        'tax_rate' => 'decimal:2',
        // Money field - stored as integer in DB
        'tax_amount' => 'integer',
        'is_compound' => 'boolean',
    ];

    /**
     * Accessors - Convert from stored integer to display float
     */
    public function getTaxAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    /**
     * Mutators - Convert from display float to stored integer
     */
    public function setTaxAmountAttribute($value): void
    {
        $this->attributes['tax_amount'] = to_base_currency($value);
    }

    /**
     * Relationships
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderCreater()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}