<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'customer_name',
        'location_id',
        'department_id',
        'order_number',
        'type',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'paid_amount',
        'balance_due',
        'source',
        'notes',
        'created_by',
    ];

    // 👇 Accessors to format currency
    public function getSubtotalAttribute($value) {
        return formatCurrency($value);
    }
    public function getDiscountTotalAttribute($value) {
        return formatCurrency($value);
    }
    public function getTaxTotalAttribute($value) {
        return formatCurrency($value);
    }
    public function getTotalAttribute($value) {
        return formatCurrency($value);
    }
    public function getPaidAmountAttribute($value) {
        return formatCurrency($value);
    }
    public function getBalanceDueAttribute($value) {
        return formatCurrency($value);
    }

        // 👇 Mutators to convert to USD when WRITING to database
    public function setSubtotalAttribute($value) {
        $this->attributes['subtotal'] = toUSD($value);
    }
    public function setDiscountTotalAttribute($value) {
        $this->attributes['discount_total'] = toUSD($value);
    }
    public function setTaxTotalAttribute($value) {
        $this->attributes['tax_total'] = toUSD($value);
    }
    public function setTotalAttribute($value) {
        $this->attributes['total'] = toUSD($value);
    }
    public function setPaidAmountAttribute($value) {
        $this->attributes['paid_amount'] = toUSD($value);
    }
    public function setBalanceDueAttribute($value) {
        $this->attributes['balance_due'] = toUSD($value);
    }

    // Relationships
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function location() { return $this->belongsTo(Location::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function orderCreater() { return $this->belongsTo(User::class, 'created_by'); }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasOne(OrderPayment::class);
    }

    // Add this relationship to your Order model
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderPayments()
    {
        return $this->hasMany(OrderPayment::class);
    }
}
