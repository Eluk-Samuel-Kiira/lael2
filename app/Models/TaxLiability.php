<?php
// app/Models/TaxLiability.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasTenant;


class TaxLiability extends Model
{
    use HasFactory, SoftDeletes, HasTenant;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'employee_payment_id',
        'tax_id',
        'amount',
        'tax_name',
        'rate',
        'tax_code',
        'tax_type',
        'status',
        'due_date',
        'remitted_at',
        'remittance_reference',
        'tax_year',
        'tax_month',
        'tax_quarter',
        'remitted_by',
        'remittance_transaction_ref',
        'remittance_payment_method_id',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'amount' => 'integer',
        'rate' => 'decimal:2',
        'due_date' => 'date',
        'remitted_at' => 'datetime',
        'metadata' => 'array',
        'tax_year' => 'integer',
        'tax_month' => 'integer',
        'tax_quarter' => 'integer',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_REMITTED = 'remitted';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Tax type constants
     */
    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED = 'fixed';

    // ============================================
    // ACCESSORS - Convert from stored integer to float
    // ============================================

    /**
     * Accessor for amount
     */
    public function getAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    // ============================================
    // MUTATORS - Convert from float to stored integer
    // ============================================

    /**
     * Mutator for amount
     */
    public function setAmountAttribute($value): void
    {
        $this->attributes['amount'] = to_base_currency($value);
    }

    // ============================================
    // ADDITIONAL ACCESSORS
    // ============================================

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2);
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        $colors = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_REMITTED => 'success',
            self::STATUS_OVERDUE => 'danger',
            self::STATUS_CANCELLED => 'secondary',
        ];

        $labels = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_REMITTED => 'Remitted',
            self::STATUS_OVERDUE => 'Overdue',
            self::STATUS_CANCELLED => 'Cancelled',
        ];

        $color = $colors[$this->status] ?? 'secondary';
        $label = $labels[$this->status] ?? ucfirst($this->status);

        return '<span class="badge badge-light-' . $color . '">' . $label . '</span>';
    }

    /**
     * Get formatted rate with percentage sign
     */
    public function getFormattedRateAttribute(): string
    {
        if ($this->tax_type === self::TYPE_FIXED) {
            return number_format($this->rate, 2);
        }
        return number_format($this->rate, 2) . '%';
    }

    /**
     * Get tax type label
     */
    public function getTaxTypeLabelAttribute(): string
    {
        return match($this->tax_type) {
            self::TYPE_PERCENTAGE => 'Percentage',
            self::TYPE_FIXED => 'Fixed Amount',
            default => ucfirst($this->tax_type),
        };
    }

    /**
     * Get period display
     */
    public function getPeriodDisplayAttribute(): string
    {
        $period = (string) $this->tax_year;
        
        if ($this->tax_month) {
            $monthName = \Carbon\Carbon::create()->month($this->tax_month)->format('F');
            $period .= ' - ' . $monthName;
        } elseif ($this->tax_quarter) {
            $period .= ' - Q' . $this->tax_quarter;
        }
        
        return $period;
    }

    /**
     * Get due date formatted
     */
    public function getDueDateFormattedAttribute(): string
    {
        return $this->due_date ? $this->due_date->format('d M Y') : '—';
    }

    /**
     * Get remitted date formatted
     */
    public function getRemittedDateFormattedAttribute(): string
    {
        return $this->remitted_at ? $this->remitted_at->format('d M Y') : '—';
    }

    /**
     * Check if liability is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING 
            && $this->due_date 
            && $this->due_date->isPast();
    }

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function employeePayment()
    {
        return $this->belongsTo(EmployeePayment::class, 'employee_payment_id');
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    public function remittedBy()
    {
        return $this->belongsTo(User::class, 'remitted_by');
    }

    public function remittancePaymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'remittance_payment_method_id');
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeRemitted($query)
    {
        return $query->where('status', self::STATUS_REMITTED);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('due_date', '<', now());
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForPeriod($query, $year, $month = null, $quarter = null)
    {
        $query->where('tax_year', $year);
        
        if ($month) {
            $query->where('tax_month', $month);
        }
        
        if ($quarter) {
            $query->where('tax_quarter', $quarter);
        }
        
        return $query;
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForPayment($query, $paymentId)
    {
        return $query->where('employee_payment_id', $paymentId);
    }

    // ============================================
    // METHODS
    // ============================================

    /**
     * Mark liability as remitted
     */
    public function markAsRemitted($remittanceData = [])
    {
        $this->update(array_merge([
            'status' => self::STATUS_REMITTED,
            'remitted_at' => now(),
        ], $remittanceData));
    }

    /**
     * Mark liability as overdue
     */
    public function markAsOverdue()
    {
        if ($this->status === self::STATUS_PENDING && $this->due_date && $this->due_date->isPast()) {
            $this->update(['status' => self::STATUS_OVERDUE]);
        }
    }

    /**
     * Mark liability as cancelled
     */
    public function markAsCancelled()
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Calculate tax amount for a given taxable amount
     */
    public function calculateTaxAmount(float $taxableAmount): float
    {
        if ($this->tax_type === self::TYPE_PERCENTAGE) {
            return $taxableAmount * ($this->rate / 100);
        }
        
        // Fixed amount
        return $this->rate;
    }

    // ============================================
    // STATIC HELPERS
    // ============================================

    /**
     * Get total pending tax amount for a tenant
     */
    public static function getPendingTotal($tenantId)
    {
        return self::where('tenant_id', $tenantId)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_OVERDUE])
            ->sum('amount');
    }

    /**
     * Get total remitted tax for a period
     */
    public static function getRemittedTotal($tenantId, $year, $month = null)
    {
        $query = self::where('tenant_id', $tenantId)
            ->where('status', self::STATUS_REMITTED)
            ->where('tax_year', $year);
        
        if ($month) {
            $query->where('tax_month', $month);
        }
        
        return $query->sum('amount');
    }

    /**
     * Get monthly tax summary
     */
    public static function getMonthlySummary($tenantId, $year)
    {
        return self::where('tenant_id', $tenantId)
            ->where('tax_year', $year)
            ->selectRaw('tax_month, 
                         SUM(CASE WHEN status = "pending" THEN amount ELSE 0 END) as pending,
                         SUM(CASE WHEN status = "remitted" THEN amount ELSE 0 END) as remitted,
                         SUM(CASE WHEN status = "overdue" THEN amount ELSE 0 END) as overdue')
            ->groupBy('tax_month')
            ->orderBy('tax_month')
            ->get();
    }

    /**
     * Get outstanding liabilities by period
     */
    public static function getOutstandingByPeriod($tenantId)
    {
        return self::where('tenant_id', $tenantId)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_OVERDUE])
            ->selectRaw('tax_year, tax_month, tax_quarter, SUM(amount) as total')
            ->groupBy('tax_year', 'tax_month', 'tax_quarter')
            ->orderBy('tax_year', 'desc')
            ->orderBy('tax_month', 'desc')
            ->get();
    }

    /**
     * Get all statuses for dropdown
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_REMITTED => 'Remitted',
            self::STATUS_OVERDUE => 'Overdue',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Get all tax types for dropdown
     */
    public static function getTaxTypes(): array
    {
        return [
            self::TYPE_PERCENTAGE => 'Percentage (%)',
            self::TYPE_FIXED => 'Fixed Amount',
        ];
    }
}