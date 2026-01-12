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
        'payment_method_id', // Changed from payment_method
        'reference_number',
        'status',
        'pay_period_start',
        'pay_period_end',
        'hours_worked',
        'hourly_rate',
        'breakdown',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'amount' => 'decimal:2',
        'hours_worked' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'breakdown' => 'array',
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
     * Get the employee that owns the payment.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the tenant that owns the payment.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the payment method for this payment.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    // 👇 Accessors for monetary fields
    public function getAmountAttribute($value)
    {
        return formatCurrency($value);
    }

    public function getHourlyRateAttribute($value)
    {
        return formatCurrency($value);
    }

    // 👇 Mutators for monetary fields - Convert to USD when WRITING to database
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = toUSD($value);
    }

    public function setHourlyRateAttribute($value)
    {
        $this->attributes['hourly_rate'] = toUSD($value);
    }

    /**
     * Get the payment method name (for backward compatibility)
     */
    public function getPaymentMethodNameAttribute()
    {
        return $this->paymentMethod ? $this->paymentMethod->name : null;
    }

    /**
     * Get the payment method type (for backward compatibility)
     */
    public function getPaymentMethodTypeAttribute()
    {
        return $this->paymentMethod ? $this->paymentMethod->type : null;
    }

    /**
     * Get the payment method code (for backward compatibility)
     */
    public function getPaymentMethodCodeAttribute()
    {
        return $this->paymentMethod ? $this->paymentMethod->code : null;
    }

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
     * Get status color.
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
     * Scope a query to only include payments for a specific tenant.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Calculate total paid amount for an employee.
     */
    public static function getTotalPaidForEmployee($employeeId)
    {
        return self::where('employee_id', $employeeId)
            ->where('status', self::STATUS_COMPLETED)
            ->sum('amount');
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
}