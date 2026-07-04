<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use SoftDeletes;

    // ============================================================
    // STATUS CONSTANTS
    // ============================================================
    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_VIEWED = 'viewed';
    const STATUS_PARTIALLY_PAID = 'partially_paid';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_VOID = 'void';
    const STATUS_CANCELLED = 'cancelled';

    const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SENT => 'Sent',
        self::STATUS_VIEWED => 'Viewed',
        self::STATUS_PARTIALLY_PAID => 'Partially Paid',
        self::STATUS_PAID => 'Paid',
        self::STATUS_OVERDUE => 'Overdue',
        self::STATUS_VOID => 'Void',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    const STATUS_COLORS = [
        self::STATUS_DRAFT => 'secondary',
        self::STATUS_SENT => 'info',
        self::STATUS_VIEWED => 'primary',
        self::STATUS_PARTIALLY_PAID => 'warning',
        self::STATUS_PAID => 'success',
        self::STATUS_OVERDUE => 'danger',
        self::STATUS_VOID => 'dark',
        self::STATUS_CANCELLED => 'danger',
    ];

    const STATUS_ICONS = [
        self::STATUS_DRAFT => 'bi-file-earmark',
        self::STATUS_SENT => 'bi-envelope',
        self::STATUS_VIEWED => 'bi-eye',
        self::STATUS_PARTIALLY_PAID => 'bi-wallet2',
        self::STATUS_PAID => 'bi-check-circle',
        self::STATUS_OVERDUE => 'bi-exclamation-diamond',
        self::STATUS_VOID => 'bi-x-circle',
        self::STATUS_CANCELLED => 'bi-x-octagon',
    ];

    // ============================================================
    // FILLABLE & CASTS
    // ============================================================
    protected $fillable = [
        'tenant_id',
        'order_id',
        'customer_id',
        'invoice_number',
        'public_token',
        'billing_name',
        'billing_email',
        'billing_phone',
        'billing_address',
        'tax_id',
        'issue_date',
        'due_date',
        'status',
        'currency',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'amount_paid',
        'balance_due',
        'pdf_path',
        'terms',
        'notes',
        'sent_at',
        'viewed_at',
        'paid_at',
        'voided_at',
        'created_by',
        'voided_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'paid_at' => 'datetime',
        'voided_at' => 'datetime',
        'subtotal' => 'integer',
        'discount_total' => 'integer',
        'tax_total' => 'integer',
        'total' => 'integer',
        'amount_paid' => 'integer',
        'balance_due' => 'integer',
    ];

    // ============================================================
    // ACCESSORS & MUTATORS
    // ============================================================
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

    public function getAmountPaidAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getBalanceDueAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

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

    public function setAmountPaidAttribute($value): void
    {
        $this->attributes['amount_paid'] = to_base_currency($value);
    }

    public function setBalanceDueAttribute($value): void
    {
        $this->attributes['balance_due'] = to_base_currency($value);
    }

    // ============================================================
    // BOOT METHOD
    // ============================================================
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber($invoice->tenant_id);
            }
            
            if (empty($invoice->public_token)) {
                $invoice->public_token = static::generatePublicToken();
            }
        });
    }

    // ============================================================
    // GENERATORS
    // ============================================================
    public static function generateInvoiceNumber($tenantId): string
    {
        $prefix = 'INV-';
        $year = date('Y');
        
        // Get the last invoice number for this tenant and year
        $lastInvoice = static::where('tenant_id', $tenantId)
            ->where('invoice_number', 'like', $prefix . $year . '-%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -5);
            $sequence = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $sequence = '00001';
        }

        $invoiceNumber = $prefix . $year . '-' . $sequence;

        // ✅ CHECK IF NUMBER ALREADY EXISTS AND INCREMENT IF NEEDED
        $attempts = 0;
        while (static::where('tenant_id', $tenantId)
            ->where('invoice_number', $invoiceNumber)
            ->exists() && $attempts < 100) {
            
            $attempts++;
            $sequenceNumber = (int) $sequence + $attempts;
            $sequence = str_pad($sequenceNumber, 5, '0', STR_PAD_LEFT);
            $invoiceNumber = $prefix . $year . '-' . $sequence;
        }

        return $invoiceNumber;
    }

    public static function generatePublicToken(): string
    {
        return Str::random(64);
    }

    // ============================================================
    // RELATIONSHIPS
    // ============================================================
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function sends()
    {
        return $this->hasMany(InvoiceSend::class);
    }



    public function webhooks()
    {
        return $this->hasMany(InvoicePaymentWebhook::class);
    }

    // ============================================================
    // HELPERS
    // ============================================================
    public function getItemsAttribute()
    {
        return $this->order ? $this->order->orderItems : collect();
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID || $this->balance_due <= 0;
    }

    public function isPartiallyPaid(): bool
    {
        return $this->status === self::STATUS_PARTIALLY_PAID || 
               ($this->amount_paid > 0 && $this->balance_due > 0);
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE || 
               ($this->due_date && $this->due_date->isPast() && $this->balance_due > 0);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isViewed(): bool
    {
        return $this->status === self::STATUS_VIEWED;
    }

    public function isVoid(): bool
    {
        return $this->status === self::STATUS_VOID;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isOutstanding(): bool
    {
        return !in_array($this->status, [self::STATUS_PAID, self::STATUS_VOID, self::STATUS_CANCELLED]) 
            && $this->balance_due > 0;
    }

    // ============================================================
    // STATUS METHODS
    // ============================================================
    public function markAsSent(): self
    {
        $this->status = self::STATUS_SENT;
        $this->sent_at = now();
        $this->save();
        return $this;
    }

    public function markAsViewed(): self
    {
        $this->status = self::STATUS_VIEWED;
        $this->viewed_at = now();
        $this->save();
        return $this;
    }

    public function markAsPaid(): self
    {
        $this->status = self::STATUS_PAID;
        $this->paid_at = now();
        $this->amount_paid = $this->total;
        $this->balance_due = 0;
        $this->save();
        return $this;
    }

    public function recordPayment(float $amount): self
    {
        $amountInBase = to_base_currency($amount);
        $this->amount_paid += $amountInBase;
        $this->balance_due = $this->total - $this->amount_paid;
        
        if ($this->balance_due <= 0) {
            $this->status = self::STATUS_PAID;
            $this->paid_at = now();
        } else {
            $this->status = self::STATUS_PARTIALLY_PAID;
        }
        
        $this->save();
        return $this;
    }

    public function void(?string $reason = null): self
    {
        $this->status = self::STATUS_VOID;
        $this->voided_at = now();
        
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Void reason: {$reason}";
        }
        
        $this->save();
        return $this;
    }

    public function updateBalances(): void
    {
        $totalPaid = $this->payments()
            ->where('status', 'completed')
            ->sum('amount');
        
        $this->amount_paid = $totalPaid;
        $this->balance_due = $this->total - $totalPaid;
        
        if ($this->balance_due <= 0 && $this->status !== self::STATUS_PAID) {
            $this->status = self::STATUS_PAID;
            $this->paid_at = now();
        } elseif ($this->balance_due > 0 && $this->amount_paid > 0) {
            $this->status = self::STATUS_PARTIALLY_PAID;
        }
        
        $this->saveQuietly();
    }

    // ============================================================
    // URL HELPERS
    // ============================================================

    /**
     * Get the payment URL for public access
     */
    public function getPaymentUrlAttribute(): string
    {
        return route('public.invoice.pay', $this->public_token);
    }

    /**
     * Get the public view URL
     */
    public function getPublicUrlAttribute(): string
    {
        return route('public.invoice.show', $this->public_token);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    public function getStatusIconAttribute(): string
    {
        return self::STATUS_ICONS[$this->status] ?? 'bi-file-earmark';
    }

    // ============================================================
    // RAW ACCESSORS
    // ============================================================
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

    public function getRawAmountPaid(): ?int
    {
        return $this->getRawOriginal('amount_paid');
    }

    public function getRawBalanceDue(): ?int
    {
        return $this->getRawOriginal('balance_due');
    }

    // ============================================================
    // SCOPES
    // ============================================================
    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeViewed($query)
    {
        return $query->where('status', self::STATUS_VIEWED);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE)
            ->orWhere(function($q) {
                $q->where('balance_due', '>', 0)
                  ->whereDate('due_date', '<', now());
            });
    }

    public function scopeOutstanding($query)
    {
        return $query->where('status', '!=', self::STATUS_PAID)
            ->where('status', '!=', self::STATUS_VOID)
            ->where('status', '!=', self::STATUS_CANCELLED)
            ->where('balance_due', '>', 0);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [
            self::STATUS_DRAFT, 
            self::STATUS_SENT, 
            self::STATUS_VIEWED, 
            self::STATUS_PARTIALLY_PAID, 
            self::STATUS_OVERDUE
        ])->where('balance_due', '>', 0);
    }

    public function scopeIssuedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('issue_date', [$startDate, $endDate]);
    }

    public function scopeBySource($query, $source)
    {
        return $query->whereHas('order', function($q) use ($source) {
            $q->where('source', $source);
        });
    }

    public function scopeDueDateBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }
}