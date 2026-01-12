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

    public function getTaxAmountAttribute($value)
    {
        return formatCurrency($value);
    }

    public function setTaxAmountAttribute($value)
    {
        $this->attributes['tax_amount'] = toUSD($value);
    }
    
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderCreater()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
