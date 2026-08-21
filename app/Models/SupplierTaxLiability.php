<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasTenant;


class SupplierTaxLiability extends Model
{
    use SoftDeletes, HasTenant;

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'purchase_order_id',
        'purchase_receipt_id',
        'expense_id',
        'tax_id',
        'taxable_amount',
        'tax_amount',
        'tax_rate',
        'tax_name',
        'tax_code',
        'tax_type',
        'reference_number',
        'transaction_date',
        'due_date',
        'status',
        'tax_year',
        'tax_month',
        'tax_quarter',
        'remitted_at',
        'remittance_reference',
        'remittance_transaction_ref',
        'remitted_by',
        'remittance_payment_method_id',
        'is_withholding_tax',
        'wht_certificate_number',
        'wht_certificate_date',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tax_rate' => 'decimal:2',
        'taxable_amount' => 'integer',
        'tax_amount' => 'integer',
        'transaction_date' => 'date',
        'due_date' => 'date',
        'remitted_at' => 'datetime',
        'wht_certificate_date' => 'date',
        'metadata' => 'array',
        'is_withholding_tax' => 'boolean',
        'tax_year' => 'integer',
        'tax_month' => 'integer',
        'tax_quarter' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'tax_type' => 'string'
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_REMITTED = 'remitted';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXEMPT = 'exempt';

    /**
     * Tax type constants
     */
    const TAX_TYPE_PERCENTAGE = 'percentage';
    const TAX_TYPE_FIXED = 'fixed';

    // ============================================
    // ACCESSORS - Convert from stored integer to float
    // ============================================

    /**
     * Accessor for taxable_amount
     */
    public function getTaxableAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    /**
     * Accessor for tax_amount
     */
    public function getTaxAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    // ============================================
    // MUTATORS - Convert from float to stored integer
    // ============================================

    /**
     * Mutator for taxable_amount
     */
    public function setTaxableAmountAttribute($value): void
    {
        $this->attributes['taxable_amount'] = to_base_currency($value);
    }

    /**
     * Mutator for tax_amount
     */
    public function setTaxAmountAttribute($value): void
    {
        $this->attributes['tax_amount'] = to_base_currency($value);
    }

    // ============================================
    // ADDITIONAL ACCESSORS
    // ============================================

    /**
     * Get formatted taxable amount
     */
    public function getFormattedTaxableAmountAttribute(): string
    {
        return number_format($this->taxable_amount, 2);
    }

    /**
     * Get formatted tax amount
     */
    public function getFormattedTaxAmountAttribute(): string
    {
        return number_format($this->tax_amount, 2);
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
            self::STATUS_EXEMPT => 'info',
        ];

        $labels = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_REMITTED => 'Remitted',
            self::STATUS_OVERDUE => 'Overdue',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_EXEMPT => 'Exempt',
        ];

        $color = $colors[$this->status] ?? 'secondary';
        $label = $labels[$this->status] ?? ucfirst($this->status);

        return '<span class="badge badge-light-' . $color . '">' . $label . '</span>';
    }

    /**
     * Get tax type badge HTML
     */
    public function getTaxTypeBadgeAttribute(): string
    {
        $colors = [
            self::TYPE_VAT => 'primary',
            self::TYPE_WITHHOLDING_TAX => 'warning',
            self::TYPE_EXCISE_DUTY => 'info',
            self::TYPE_IMPORT_DUTY => 'danger',
            self::TYPE_OTHER => 'secondary',
        ];

        $labels = [
            self::TYPE_VAT => 'VAT',
            self::TYPE_WITHHOLDING_TAX => 'Withholding Tax',
            self::TYPE_EXCISE_DUTY => 'Excise Duty',
            self::TYPE_IMPORT_DUTY => 'Import Duty',
            self::TYPE_OTHER => 'Other',
        ];

        $color = $colors[$this->tax_type] ?? 'secondary';
        $label = $labels[$this->tax_type] ?? ucfirst($this->tax_type);

        return '<span class="badge badge-light-' . $color . '">' . $label . '</span>';
    }

    /**
     * Get formatted rate with percentage sign
     */
    public function getFormattedRateAttribute(): string
    {
        return number_format($this->tax_rate, 2) . '%';
    }

    /**
     * Get period display (e.g., "2024 - Q1" or "2024 - January")
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

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function purchaseReceipt()
    {
        return $this->belongsTo(PurchaseReceipt::class, 'purchase_receipt_id');
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
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

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

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

    public function scopeForSupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
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

    public function scopeForTaxType($query, $type)
    {
        return $query->where('tax_type', $type);
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
     * Calculate total pending tax amount for a tenant
     */
    public static function getTotalPending($tenantId)
    {
        return self::where('tenant_id', $tenantId)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_OVERDUE])
            ->sum('tax_amount');
    }

    /**
     * Calculate total tax remitted for a period
     */
    public static function getTotalRemitted($tenantId, $year, $month = null)
    {
        $query = self::where('tenant_id', $tenantId)
            ->where('status', self::STATUS_REMITTED)
            ->where('tax_year', $year);
        
        if ($month) {
            $query->where('tax_month', $month);
        }
        
        return $query->sum('tax_amount');
    }

    /**
     * Get tax liabilities grouped by type
     */
    public static function getSummaryByType($tenantId)
    {
        return self::where('tenant_id', $tenantId)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_OVERDUE])
            ->selectRaw('tax_type, SUM(tax_amount) as total_amount')
            ->groupBy('tax_type')
            ->get()
            ->map(function($item) {
                return [
                    'type' => $item->tax_type,
                    'type_label' => self::getTypeLabel($item->tax_type),
                    'total_amount' => $item->total_amount,
                ];
            });
    }

    /**
     * Get human-readable tax type label
     */
    public static function getTypeLabel($type): string
    {
        return match($type) {
            self::TYPE_VAT => 'VAT',
            self::TYPE_WITHHOLDING_TAX => 'Withholding Tax',
            self::TYPE_EXCISE_DUTY => 'Excise Duty',
            self::TYPE_IMPORT_DUTY => 'Import Duty',
            default => 'Other Tax',
        };
    }

    /**
     * Get all tax types for dropdown
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_VAT => 'VAT',
            self::TYPE_WITHHOLDING_TAX => 'Withholding Tax',
            self::TYPE_EXCISE_DUTY => 'Excise Duty',
            self::TYPE_IMPORT_DUTY => 'Import Duty',
            self::TYPE_OTHER => 'Other',
        ];
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
            self::STATUS_EXEMPT => 'Exempt',
        ];
    }
}