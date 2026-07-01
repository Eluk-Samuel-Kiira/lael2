<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use SoftDeletes;

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

    public function getAmountPaidAttribute(?int $value): ?float
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

    public function setAmountPaidAttribute($value): void
    {
        $this->attributes['amount_paid'] = to_base_currency($value);
    }

    public function setBalanceDueAttribute($value): void
    {
        $this->attributes['balance_due'] = to_base_currency($value);
    }

    /**
     * Boot method for auto-generating invoice number and public token
     */
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

    /**
     * Generate sequential invoice number per tenant
     */
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
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $sequence = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $sequence = '0001';
        }

        return $prefix . $year . '-' . $sequence;
    }

    /**
     * Generate unique public token for invoice
     */
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

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function sends()
    {
        return $this->hasMany(InvoiceSend::class);
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function webhooks()
    {
        return $this->hasMany(InvoicePaymentWebhook::class);
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    /**
     * Get order items through the order relationship
     */
    public function getItemsAttribute()
    {
        return $this->order ? $this->order->orderItems : collect();
    }

    /**
     * Check if invoice is paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid' || $this->balance_due <= 0;
    }

    /**
     * Check if invoice is partially paid
     */
    public function isPartiallyPaid(): bool
    {
        return $this->status === 'partially_paid' || 
               ($this->amount_paid > 0 && $this->balance_due > 0);
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === 'overdue' || 
               ($this->due_date && $this->due_date->isPast() && $this->balance_due > 0);
    }

    /**
     * Check if invoice is draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if invoice is sent
     */
    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * Check if invoice is void
     */
    public function isVoid(): bool
    {
        return $this->status === 'void';
    }

    /**
     * Check if invoice is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Mark invoice as sent
     */
    public function markAsSent(): self
    {
        $this->status = 'sent';
        $this->sent_at = now();
        $this->save();
        
        return $this;
    }

    /**
     * Mark invoice as viewed
     */
    public function markAsViewed(): self
    {
        $this->status = 'viewed';
        $this->viewed_at = now();
        $this->save();
        
        return $this;
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(): self
    {
        $this->status = 'paid';
        $this->paid_at = now();
        $this->amount_paid = $this->total;
        $this->balance_due = 0;
        $this->save();
        
        return $this;
    }

    /**
     * Record partial payment
     */
    public function recordPayment(float $amount): self
    {
        $this->amount_paid += $amount;
        $this->balance_due = $this->total - $this->amount_paid;
        
        if ($this->balance_due <= 0) {
            $this->status = 'paid';
            $this->paid_at = now();
        } else {
            $this->status = 'partially_paid';
        }
        
        $this->save();
        
        return $this;
    }

    /**
     * Void the invoice
     */
    public function void(?string $reason = null): self
    {
        $this->status = 'void';
        $this->voided_at = now();
        
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Void reason: {$reason}";
        }
        
        $this->save();
        
        return $this;
    }

    /**
     * Update balances from payments
     */
    public function updateBalances(): void
    {
        $totalPaid = $this->payments()
            ->where('status', 'completed')
            ->sum('amount');
        
        $this->amount_paid = $totalPaid;
        $this->balance_due = $this->total - $totalPaid;
        
        if ($this->balance_due <= 0 && $this->status !== 'paid') {
            $this->status = 'paid';
            $this->paid_at = now();
        } elseif ($this->balance_due > 0 && $this->amount_paid > 0) {
            $this->status = 'partially_paid';
        }
        
        $this->saveQuietly();
    }

    /**
     * Get public URL for invoice
     */
    public function getPublicUrlAttribute(): string
    {
        return route('public.invoice.show', ['token' => $this->public_token]);
    }

    /**
     * Get payment URL for invoice
     */
    public function getPaymentUrlAttribute(): string
    {
        return route('public.invoice.pay', ['token' => $this->public_token]);
    }

    /**
     * Get raw amounts for calculations
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
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function($q) {
                $q->where('balance_due', '>', 0)
                  ->whereDate('due_date', '<', now());
            });
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['draft', 'sent', 'viewed', 'partially_paid', 'overdue'])
            ->where('balance_due', '>', 0);
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
}