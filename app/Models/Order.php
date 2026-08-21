<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenant;


class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory, HasTenant;

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
        'subtotal_before_bargain',     
        'bargain_discount_applied',
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
        'subtotal_before_bargain' => 'integer',     
        'bargain_discount_applied' => 'integer',
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
     * ✅ Accessor for subtotal_before_bargain
     * The order's total BEFORE any bargain discount was ever applied.
     * Set once, on first discount application, and never re-derived.
     */
    public function getSubtotalBeforeBargainAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    /**
     * ✅ Accessor for bargain_discount_applied
     * The bargain-discount component of discount_total, tracked separately
     * from any item-level/promotion discounts.
     */
    public function getBargainDiscountAppliedAttribute(?int $value): ?float
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

    /**
     * ✅ Mutator for subtotal_before_bargain
     */
    public function setSubtotalBeforeBargainAttribute($value): void
    {
        $this->attributes['subtotal_before_bargain'] = to_base_currency($value);
    }

    /**
     * ✅ Mutator for bargain_discount_applied
     */
    public function setBargainDiscountAppliedAttribute($value): void
    {
        $this->attributes['bargain_discount_applied'] = to_base_currency($value);
    }






    // Relationships
    public function tenant() 
    { 
        return $this->belongsTo(Tenant::class); 
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'order_id', 'id');
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

    public function createdBy()
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



    /**
     * Accessor for formatted created date
     */
    public function getCreatedDateAttribute()
    {
        return $this->created_at->format('Y-m-d');
    }

    /**
     * Accessor for formatted week period
     */
    public function getWeekPeriodAttribute()
    {
        $weekStart = $this->created_at->copy()->startOfWeek();
        $weekEnd = $this->created_at->copy()->endOfWeek();
        return [
            'year' => $this->created_at->year,
            'week_number' => $this->created_at->weekOfYear,
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end' => $weekEnd->format('Y-m-d'),
            'period' => $weekStart->format('M d') . ' - ' . $weekEnd->format('M d')
        ];
    }

    /**
     * Accessor for formatted month period
     */
    public function getMonthPeriodAttribute()
    {
        return [
            'year' => $this->created_at->year,
            'month_number' => $this->created_at->month,
            'month_name' => $this->created_at->format('F'),
            'period' => $this->created_at->format('Y-m')
        ];
    }

    /**
     * Local scope for date range
     */
    public function scopeDateBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [
            $startDate . ' 00:00:00',
            $endDate . ' 23:59:59'
        ]);
    }

    /**
     * Local scope for completed orders only
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['completed', 'processing']);
    }

    /**
     * Local scope by location
     */
    public function scopeByLocation($query, $locationId)
    {
        if ($locationId) {
            return $query->where('location_id', $locationId);
        }
        return $query;
    }

    /**
     * Local scope by department
     */
    public function scopeByDepartment($query, $departmentId)
    {
        if ($departmentId) {
            return $query->where('department_id', $departmentId);
        }
        return $query;
    }

    /**
     * Local scope by order type
     */
    public function scopeByOrderType($query, $orderType)
    {
        if ($orderType && $orderType !== 'all') {
            return $query->where('type', $orderType);
        }
        return $query;
    }
    

    
    /**
     * Check if order is a return order
     */
    public function getIsReturnOrderAttribute()
    {
        return $this->type === 'return' || $this->status === 'refunded';
    }

    /**
     * Get return reason from notes
     */
    public function getReturnReasonAttribute()
    {
        if (empty($this->notes)) {
            return 'No reason provided';
        }
        
        // Extract reason from notes (if formatted as "Reason: description")
        if (strpos($this->notes, ':') !== false) {
            return substr($this->notes, 0, strpos($this->notes, ':'));
        }
        
        return 'Other';
    }

    /**
     * Get return reason description
     */
    public function getReturnReasonDescriptionAttribute()
    {
        if (empty($this->notes)) {
            return null;
        }
        
        if (strpos($this->notes, ':') !== false) {
            return trim(substr($this->notes, strpos($this->notes, ':') + 1));
        }
        
        return $this->notes;
    }

    /**
     * Relationship with refund payments
     */
    public function refundPayments()
    {
        return $this->hasMany(OrderPayment::class)->where('status', 'refunded');
    }

    /**
     * Relationship with returned items
     */
    public function returnedItems()
    {
        return $this->hasMany(OrderItem::class)->whereHas('order', function($query) {
            $query->where('type', 'return');
        });
    }

    /**
     * Scope for return orders
     */
    public function scopeReturns($query)
    {
        return $query->where(function($q) {
            $q->where('type', 'return')
            ->orWhere('status', 'refunded');
        });
    }

    /**
     * Scope for sale orders only
     */
    public function scopeSales($query)
    {
        return $query->where('type', 'sale');
    }

    /**
     * Scope for active orders (not returns)
     */
    public function scopeActiveOrders($query)
    {
        return $query->where('type', '!=', 'return')
                    ->where('status', '!=', 'refunded');
    }


    /**
     * Check if order has discount
     */
    public function getHasDiscountAttribute()
    {
        return $this->discount_total > 0;
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentageAttribute()
    {
        if ($this->total > 0) {
            return ($this->discount_total / $this->total) * 100;
        }
        return 0;
    }

    /**
     * Get final amount after discount
     */
    public function getFinalAmountAttribute()
    {
        return $this->total - $this->discount_total;
    }

    /**
     * Get created hour of day
     */
    public function getCreatedHourAttribute()
    {
        return $this->created_at->hour;
    }

    /**
     * Get day of week (1-7, 1 = Sunday)
     */
    public function getDayOfWeekAttribute()
    {
        return $this->created_at->dayOfWeek;
    }

    /**
     * Get day name
     */
    public function getDayNameAttribute()
    {
        if ($this->created_at) {
            return $this->created_at->format('l');
        }
        
        return null; // or return a default value like 'Unknown'
    }

    /**
     * Scope for orders with discounts
     */
    public function scopeWithDiscounts($query)
    {
        return $query->where('discount_total', '>', 0);
    }

    /**
     * Scope for orders without discounts
     */
    public function scopeWithoutDiscounts($query)
    {
        return $query->where('discount_total', 0);
    }


    /**
     * Get customer display name (handles both registered and guest customers)
     */
    public function getCustomerDisplayNameAttribute()
    {
        // If there's a customer relationship loaded and customer exists
        if ($this->relationLoaded('customer') && $this->customer) {
            return $this->customer->name;
        }
        
        // If customer_id exists but relationship not loaded, try to get from database
        if ($this->customer_id && !$this->relationLoaded('customer')) {
            $customer = Customer::find($this->customer_id);
            return $customer ? $customer->name : ($this->customer_name ?: 'Guest');
        }
        
        // If customer_id exists but relationship is loaded and customer is null
        if ($this->customer_id && $this->customer === null) {
            return $this->customer_name ?: 'Deleted Customer';
        }
        
        // If customer_name is provided (guest checkout)
        if ($this->customer_name) {
            return $this->customer_name;
        }
        
        // Fallback
        return 'Guest';
    }

    /**
     * Get customer email (handles both registered and guest customers)
     */
    public function getCustomerEmailAttribute()
    {
        if ($this->relationLoaded('customer') && $this->customer) {
            return $this->customer->email;
        }
        
        return null;
    }

    /**
     * Get customer info as array
     */
    public function getCustomerInfoAttribute()
    {
        return [
            'id' => $this->customer_id,
            'name' => $this->customer_display_name,
            'email' => $this->customer_email,
            'is_registered' => !is_null($this->customer_id) && $this->customer_id > 0,
            'is_guest' => is_null($this->customer_id) && !is_null($this->customer_name),
        ];
    }

    /**
     * Check if customer is registered
     */
    public function getIsRegisteredCustomerAttribute()
    {
        return !is_null($this->customer_id) && $this->customer_id > 0;
    }

    /**
     * Check if customer is guest
     */
    public function getIsGuestCustomerAttribute()
    {
        return is_null($this->customer_id) && !is_null($this->customer_name);
    }


    /**
     * Get date only (Y-m-d)
     */
    public function getDateOnlyAttribute()
    {
        return $this->created_at->format('Y-m-d');
    }
}
