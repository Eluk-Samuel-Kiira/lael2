<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasTenant;


class OrderPayment extends Model
{
    /** @use HasFactory<\Database\Factories\OrderPaymentFactory> */
    use HasFactory, HasTenant;

    protected $fillable = [
        'order_id',
        'invoice_id',
        'amount',
        'payment_method_id',
        'transaction_id',
        'status',
        'recorded_via',
        'webhook_id',
        'confirmed_by',
        'card_last_four',
        'card_brand',
        'notes',
        'processed_at',
        'processed_by',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        // Money field - stored as integer in DB
        'amount' => 'integer',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    // Recorded-via constants — how this payment row came to exist
    const RECORDED_VIA_POS = 'pos';
    const RECORDED_VIA_MANUAL = 'manual';
    const RECORDED_VIA_WEBHOOK = 'webhook';

    /**
     * Accessors - Convert from stored integer to display float
     */
    public function getAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    /**
     * Mutators - Convert from display float to stored integer
     */
    public function setAmountAttribute($value): void
    {
        $this->attributes['amount'] = to_base_currency($value);
    }

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(InvoicePaymentWebhook::class, 'webhook_id');
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

    public function scopeForInvoice($query, $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    public function scopeRecordedVia($query, $method)
    {
        return $query->where('recorded_via', $method);
    }

    public function scopeManuallyConfirmed($query)
    {
        return $query->where('recorded_via', self::RECORDED_VIA_MANUAL);
    }

    public function scopeFromWebhook($query)
    {
        return $query->where('recorded_via', self::RECORDED_VIA_WEBHOOK);
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

    public function isForInvoice(): bool
    {
        return $this->invoice_id !== null;
    }

    public function wasRecordedManually(): bool
    {
        return $this->recorded_via === self::RECORDED_VIA_MANUAL;
    }

    public function wasRecordedViaWebhook(): bool
    {
        return $this->recorded_via === self::RECORDED_VIA_WEBHOOK;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
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