<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_variant_id',
        'payment_method_id',
        'product_name',
        'sku',
        'quantity',
        'unit_cost',
        'tax_amount',
        'total_cost',
        'received_quantity',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'quantity' => 'integer',
        'received_quantity' => 'integer',
    ];

    // 👇 Accessors for monetary fields
    public function getUnitCostAttribute($value)
    {
        return formatCurrency($value);
    }

    public function getTaxAmountAttribute($value)
    {
        return formatCurrency($value);
    }

    public function getTotalCostAttribute($value)
    {
        return formatCurrency($value);
    }

    // 👇 Mutators for monetary fields
    public function setUnitCostAttribute($value)
    {
        $this->attributes['unit_cost'] = toUSD($value);
    }

    public function setTaxAmountAttribute($value)
    {
        $this->attributes['tax_amount'] = toUSD($value);
    }

    public function setTotalCostAttribute($value)
    {
        $this->attributes['total_cost'] = toUSD($value);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function receiptItems(): HasMany
    {
        return $this->hasMany(PurchaseReceiptItem::class, 'purchase_order_item_id');
    }

    public function scopeByPaymentMethod($query, $paymentMethodId)
    {
        return $query->where('payment_method_id', $paymentMethodId);
    }

    public function scopeByPaymentStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    public function scopePendingPayment($query)
    {
        return $query->whereIn('payment_status', [self::PAYMENT_STATUS_PENDING, self::PAYMENT_STATUS_PARTIAL]);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', self::PAYMENT_STATUS_PAID);
    }

    // Methods
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
    public function getPaymentMethodNameAttribute()
    {
        return $this->paymentMethod ? $this->paymentMethod->name : 'Not assigned';
    }

    public function getPaymentMethodTypeAttribute()
    {
        return $this->paymentMethod ? $this->paymentMethod->type : 'N/A';
    }

    public function getFormattedPaymentDateAttribute()
    {
        return $this->payment_date ? $this->payment_date->format('M d, Y') : 'Not paid';
    }

    public function getRemainingQuantityAttribute()
    {
        return max(0, $this->quantity - $this->received_quantity);
    }

    public function getPaymentStatusColorAttribute(): string
    {
        return match($this->payment_status) {
            self::PAYMENT_STATUS_PENDING => 'warning',
            self::PAYMENT_STATUS_PARTIAL => 'info',
            self::PAYMENT_STATUS_PAID => 'success',
            self::PAYMENT_STATUS_OVERDUE => 'danger',
            default => 'secondary',
        };
    }

    public function isFullyPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_PAID;
    }

    public function isPartiallyPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_PARTIAL;
    }

    public function isPendingPayment(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_PENDING;
    }

    public function isOverdue(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_OVERDUE;
    }
}