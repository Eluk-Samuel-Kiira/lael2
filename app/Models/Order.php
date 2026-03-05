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

    protected $casts = [
        // Money fields - stored as integers in DB
        'subtotal' => 'integer',
        'discount_total' => 'integer',
        'tax_total' => 'integer',
        'total' => 'integer',
        'paid_amount' => 'integer',
        'balance_due' => 'integer',
    ];

    /**
     * Accessors - Convert from stored integer to display float
     */
    public function getSubtotalAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getDiscountTotalAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getTaxTotalAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getTotalAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getPaidAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getBalanceDueAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    /**
     * Mutators - Convert from display float to stored integer
     */
    public function setSubtotalAttribute($value): void
    {
        $this->attributes['subtotal'] = to_base_currency($value);
    }

    public function setDiscountTotalAttribute($value): void
    {
        $this->attributes['discount_total'] = to_base_currency($value);
    }

    public function setTaxTotalAttribute($value): void
    {
        $this->attributes['tax_total'] = to_base_currency($value);
    }

    public function setTotalAttribute($value): void
    {
        $this->attributes['total'] = to_base_currency($value);
    }

    public function setPaidAmountAttribute($value): void
    {
        $this->attributes['paid_amount'] = to_base_currency($value);
    }

    public function setBalanceDueAttribute($value): void
    {
        $this->attributes['balance_due'] = to_base_currency($value);
    }

    // Relationships
    public function tenant() 
    { 
        return $this->belongsTo(Tenant::class); 
    }
    
    public function customer() 
    { 
        return $this->belongsTo(Customer::class); 
    }
    
    public function location() 
    { 
        return $this->belongsTo(Location::class); 
    }
    
    public function department() 
    { 
        return $this->belongsTo(Department::class); 
    }
    
    public function orderCreater() 
    { 
        return $this->belongsTo(User::class, 'created_by'); 
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Alias for orderItems
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderPayments()
    {
        return $this->hasMany(OrderPayment::class);
    }

    /**
     * Helper Methods
     */
    public function isPaid(): bool
    {
        return $this->balance_due <= 0;
    }

    public function isPartial(): bool
    {
        return $this->paid_amount > 0 && $this->balance_due > 0;
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function updateBalances(): void
    {
        $totalPaid = $this->orderPayments()
            ->where('status', 'completed')
            ->sum('amount');
        
        $this->paid_amount = $totalPaid;
        $this->balance_due = $this->total - $totalPaid;
        $this->saveQuietly(); // Save without triggering events
    }

    /**
     * Get raw amounts for calculations (stored integers)
     */
    public function getRawSubtotal(): ?int
    {
        return $this->getRawOriginal('subtotal');
    }

    public function getRawDiscountTotal(): ?int
    {
        return $this->getRawOriginal('discount_total');
    }

    public function getRawTaxTotal(): ?int
    {
        return $this->getRawOriginal('tax_total');
    }

    public function getRawTotal(): ?int
    {
        return $this->getRawOriginal('total');
    }

    public function getRawPaidAmount(): ?int
    {
        return $this->getRawOriginal('paid_amount');
    }

    public function getRawBalanceDue(): ?int
    {
        return $this->getRawOriginal('balance_due');
    }
    
}
