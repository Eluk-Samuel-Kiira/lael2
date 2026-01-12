<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    /** @use HasFactory<\Database\Factories\OrderPaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'amount',
        'payment_method_id', 
        'transaction_id',
        'status',
        'card_last_four',
        'card_brand',
        'notes',
        'processed_at',
        'processed_by',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    // Accessor to format amount automatically
    public function getAmountAttribute($value)
    {
        return formatCurrency($value);
    }

    // Mutator to convert to USD when WRITING to database
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = toUSD($value);
    }

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Accessors for backward compatibility
    public function getPaymentMethodNameAttribute()
    {
        return $this->paymentMethod ? $this->paymentMethod->name : null;
    }

    public function getPaymentMethodTypeAttribute()
    {
        return $this->paymentMethod ? $this->paymentMethod->type : null;
    }

    public function getPaymentMethodCodeAttribute()
    {
        return $this->paymentMethod ? $this->paymentMethod->code : null;
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeByPaymentMethod($query, $paymentMethodId)
    {
        return $query->where('payment_method_id', $paymentMethodId);
    }

    // Methods
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_COMPLETED => 'success',
            self::STATUS_PENDING => 'warning',
            self::STATUS_FAILED => 'danger',
            self::STATUS_REFUNDED => 'info',
            default => 'secondary',
        };
    }

    public function getFormattedProcessedAtAttribute()
    {
        return $this->processed_at ? $this->processed_at->format('M d, Y H:i') : 'Not processed';
    }
}