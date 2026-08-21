<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasTenant;

class InvoicePayment extends Model
{
    use HasFactory, SoftDeletes, HasTenant;

    protected $fillable = [
        'invoice_id',
        'order_payment_id',
        'payment_method_id',
        'processed_by',
        'transaction_id',
        'reference_number',
        'amount',
        'currency_code',
        'status',
        'type',
        'payment_date',
        'verified_at',
        'refunded_at',
        'notes',
        'metadata',
        'gateway_response',
    ];

    protected $casts = [
        'amount' => 'integer',
        'payment_date' => 'datetime',
        'verified_at' => 'datetime',
        'refunded_at' => 'datetime',
        'metadata' => 'array',
        'gateway_response' => 'array',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_PENDING_VERIFICATION = 'pending_verification';

    // Type constants
    const TYPE_FULL = 'full';
    const TYPE_PARTIAL = 'partial';
    const TYPE_DEPOSIT = 'deposit';

    /**
     * Accessor - Convert from stored integer to display float
     */
    public function getAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    /**
     * Mutator - Convert from display float to stored integer
     */
    public function setAmountAttribute($value): void
    {
        $this->attributes['amount'] = to_base_currency($value);
    }

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function orderPayment()
    {
        return $this->belongsTo(OrderPayment::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

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

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function isFull(): bool
    {
        return $this->type === self::TYPE_FULL;
    }

    public function isPartial(): bool
    {
        return $this->type === self::TYPE_PARTIAL;
    }

    public function markAsCompleted(): self
    {
        $this->status = self::STATUS_COMPLETED;
        $this->verified_at = now();
        $this->save();
        
        return $this;
    }

    public function markAsPending(): self
    {
        $this->status = self::STATUS_PENDING;
        $this->save();
        
        return $this;
    }

    public function markAsFailed(string $notes = null): self
    {
        $this->status = self::STATUS_FAILED;
        $this->notes = $notes ?? $this->notes;
        $this->save();
        
        return $this;
    }

    public function markAsRefunded(): self
    {
        $this->status = self::STATUS_REFUNDED;
        $this->refunded_at = now();
        $this->save();
        
        return $this;
    }

    public function markAsVerified(): self
    {
        $this->verified_at = now();
        $this->save();
        
        return $this;
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_REFUNDED => 'Refunded',
            self::STATUS_PENDING_VERIFICATION => 'Pending Verification',
        ];
        
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_REFUNDED => 'secondary',
            self::STATUS_PENDING_VERIFICATION => 'info',
        ];
        
        return $colors[$this->status] ?? 'secondary';
    }

    public function getTypeLabelAttribute(): string
    {
        $labels = [
            self::TYPE_FULL => 'Full Payment',
            self::TYPE_PARTIAL => 'Partial Payment',
            self::TYPE_DEPOSIT => 'Deposit',
        ];
        
        return $labels[$this->type] ?? $this->type;
    }
}