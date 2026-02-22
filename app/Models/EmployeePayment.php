<?php
// app/Models/EmployeePayment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'tenant_id',
        'payment_date',
        'payment_type',
        'description',
        'amount',
        'gross_amount',
        'net_amount',
        'total_tax_amount',
        'applied_taxes',
        'is_tax_computed',
        'payment_method_id',
        'reference_number',
        'status',
        'pay_period_start',
        'pay_period_end',
        'hours_worked',
        'hourly_rate',
        'breakdown',
        'notes',
        'completed_at',
        'failed_at',
        'cancelled_at',
    ];

protected $casts = [
        'payment_date' => 'date',
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'failed_at' => 'date',
        'completed_at' => 'date',
        // Money fields - stored as integers in DB
        'amount' => 'integer',
        'gross_amount' => 'integer',
        'net_amount' => 'integer',
        'total_tax_amount' => 'integer',
        'hours_worked' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'breakdown' => 'json',
        'applied_taxes' => 'json',
        'is_tax_computed' => 'boolean',
    ];

   /**
     * Payment type constants.
     */
    const TYPE_SALARY = 'salary';
    const TYPE_ALLOWANCE = 'allowance';
    const TYPE_BONUS = 'bonus';
    const TYPE_OVERTIME = 'overtime';
    const TYPE_ADVANCE = 'advance';
    const TYPE_OTHER = 'other';

    /**
     * Status constants.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';



    /**
     * Accessors - Convert from stored integer to display float
     */
    public function getAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getGrossAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getNetAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getTotalTaxAmountAttribute(?int $value): ?float
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

    public function setGrossAmountAttribute($value): void
    {
        $this->attributes['gross_amount'] = to_base_currency($value);
    }

    public function setNetAmountAttribute($value): void
    {
        $this->attributes['net_amount'] = to_base_currency($value);
    }

    public function setTotalTaxAmountAttribute($value): void
    {
        $this->attributes['total_tax_amount'] = to_base_currency($value);
    }


    /**
     * Get the tenant that owns the employee.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the tenant that owns the employee.
     */
    public function employee() : BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the tenant that owns the employee.
     */
    public function paymentMethod() : BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'id');
    }

    /**
     * Get status color for badges.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_COMPLETED => 'success',
            self::STATUS_PENDING => 'warning',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            default => 'info',
        };
    }

    /**
     * Get status badge HTML.
     */
    public function getStatusBadgeAttribute(): string
    {
        $labels = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];

        $colors = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
        ];

        $label = $labels[$this->status] ?? ucfirst($this->status);
        $color = $colors[$this->status] ?? 'primary';

        return '<span class="badge badge-light-' . $color . '">' . $label . '</span>';
    }

    /**
     * Get payment type label.
     */
    public function getPaymentTypeLabelAttribute(): string
    {
        $types = self::getPaymentTypes();
        return $types[$this->payment_type] ?? ucfirst($this->payment_type);
    }

    /**
     * Get payment type badge.
     */
    public function getPaymentTypeBadgeAttribute(): string
    {
        $colors = [
            self::TYPE_SALARY => 'primary',
            self::TYPE_ALLOWANCE => 'info',
            self::TYPE_BONUS => 'success',
            self::TYPE_OVERTIME => 'warning',
            self::TYPE_ADVANCE => 'danger',
            self::TYPE_OTHER => 'secondary',
        ];

        $color = $colors[$this->payment_type] ?? 'primary';
        $label = $this->payment_type_label;

        return '<span class="badge badge-light-' . $color . '">' . $label . '</span>';
    }

    /**
     * Get payment method with badge.
     */
    public function getPaymentMethodWithBadgeAttribute(): ?string
    {
        if (!$this->paymentMethod) {
            return null;
        }

        $typeColors = [
            'cash' => 'warning',
            'bank_account' => 'info',
            'digital_wallet' => 'primary',
            'card' => 'success',
            'check' => 'secondary',
            'mobile_money' => 'danger',
            'other' => 'dark'
        ];

        $color = $typeColors[$this->paymentMethod->type] ?? 'dark';
        
        return '<span class="badge badge-light-' . $color . '">' . 
               $this->paymentMethod->name . 
               ($this->paymentMethod->is_default ? ' (Default)' : '') . 
               '</span>';
    }



    /**
     * ===========================================
     * SCOPES
     * ===========================================
     */

    /**
     * Scope completed payments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope payments by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Scope payments by payment method.
     */
    public function scopeByPaymentMethod($query, $paymentMethodId)
    {
        return $query->where('payment_method_id', $paymentMethodId);
    }

    /**
     * Scope a query to only include payments for a specific tenant.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope payments with tax computed.
     */
    public function scopeWithTaxComputed($query)
    {
        return $query->where('is_tax_computed', true);
    }

    /**
     * Scope payments without tax computed.
     */
    public function scopeWithoutTaxComputed($query)
    {
        return $query->where('is_tax_computed', false);
    }

    /**
     * Scope payments by payment type.
     */
    public function scopeByPaymentType($query, $type)
    {
        return $query->where('payment_type', $type);
    }

    /**
     * ===========================================
     * HELPER METHODS
     * ===========================================
     */

    /**
     * Get payment type options.
     */
    public static function getPaymentTypes(): array
    {
        return [
            self::TYPE_SALARY => 'Salary',
            self::TYPE_ALLOWANCE => 'Allowance',
            self::TYPE_BONUS => 'Bonus',
            self::TYPE_OVERTIME => 'Overtime',
            self::TYPE_ADVANCE => 'Advance',
            self::TYPE_OTHER => 'Other',
        ];
    }

    /**
     * Get status options.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Calculate total paid amount for an employee.
     */
    public static function getTotalPaidForEmployee($employeeId): float
    {
        return (float) self::where('employee_id', $employeeId)
            ->where('status', self::STATUS_COMPLETED)
            ->sum('amount');
    }

    /**
     * Calculate total tax deducted for an employee.
     */
    public static function getTotalTaxForEmployee($employeeId): float
    {
        return (float) self::where('employee_id', $employeeId)
            ->where('status', self::STATUS_COMPLETED)
            ->where('is_tax_computed', true)
            ->sum('total_tax_amount');
    }

    /**
     * Get payments by employee with payment method details.
     */
    public static function getPaymentsWithDetails($employeeId = null)
    {
        $query = self::with(['employee', 'paymentMethod'])
            ->orderBy('payment_date', 'desc');

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        return $query;
    }

    /**
     * Check if payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if payment has taxes applied.
     */
    public function hasTaxes(): bool
    {
        return $this->is_tax_computed && !empty($this->applied_taxes);
    }

    /**
     * Get the number of taxes applied.
     */
    public function getTaxCountAttribute(): int
    {
        return $this->hasTaxes() ? count($this->applied_taxes) : 0;
    }

    /**
     * Mark payment as completed.
     */
    public function markAsCompleted(): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark payment as failed.
     */
    public function markAsFailed(): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
        ]);
    }

    /**
     * Mark payment as cancelled.
     */
    public function markAsCancelled(): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }
}