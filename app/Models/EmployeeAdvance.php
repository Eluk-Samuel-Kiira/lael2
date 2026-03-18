<?php
// app/Models/EmployeeAdvance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeAdvance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'tenant_id',
        'payment_id',
        'advance_amount',
        'remaining_amount',
        'advance_date',
        'request_date',
        'approval_date',
        'deduction_frequency',
        'installments',
        'installments_paid',
        'installment_amount',
        'deduction_day',
        'deduction_start_date',
        'deduction_end_date',
        'applicable_salary_types',
        'purpose',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'deduction_schedule',
        'deduction_history',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'advance_date' => 'date',
        'request_date' => 'date',
        'approval_date' => 'date',
        'deduction_start_date' => 'date',
        'deduction_end_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'installments' => 'integer',
        'installments_paid' => 'integer',
        'deduction_day' => 'integer',
        'applicable_salary_types' => 'array',
        'deduction_schedule' => 'array',
        'deduction_history' => 'array',
        // Note: Monetary fields are NOT cast to integer - they use accessors/mutators
    ];

    /**
     * ===========================================
     * ACCESSORS & MUTATORS (Using BIGINT pattern)
     * ===========================================
     */

    /**
     * Accessor for advance_amount - converts from stored integer to display float
     */
    public function getAdvanceAmountAttribute($value): ?float
    {
        return $value ? $value / 100 : 0;
    }

    /**
     * Mutator for advance_amount - converts from display float to stored integer
     */
    public function setAdvanceAmountAttribute($value): void
    {
        $this->attributes['advance_amount'] = $value ? round($value * 100) : 0;
    }

    /**
     * Accessor for remaining_amount - converts from stored integer to display float
     */
    public function getRemainingAmountAttribute($value): ?float
    {
        return $value ? $value / 100 : 0;
    }

    /**
     * Mutator for remaining_amount - converts from display float to stored integer
     */
    public function setRemainingAmountAttribute($value): void
    {
        $this->attributes['remaining_amount'] = $value ? round($value * 100) : 0;
    }

    /**
     * Accessor for installment_amount - converts from stored integer to display float
     */
    public function getInstallmentAmountAttribute($value): ?float
    {
        return $value ? $value / 100 : 0;
    }

    /**
     * Mutator for installment_amount - converts from display float to stored integer
     */
    public function setInstallmentAmountAttribute($value): void
    {
        $this->attributes['installment_amount'] = $value ? round($value * 100) : 0;
    }

    /**
     * ===========================================
     * RELATIONSHIPS
     * ===========================================
     */

    /**
     * Get the employee that owns the advance.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the tenant that owns the advance.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the payment associated with this advance.
     */
    public function payment()
    {
        return $this->belongsTo(EmployeePayment::class, 'payment_id');
    }

    /**
     * Get the user who approved this advance.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who rejected this advance.
     */
    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get the user who created this advance.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * ===========================================
     * SCOPES
     * ===========================================
     */

    /**
     * Scope for tenant filtering.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope for pending advances.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved advances.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for active advances (approved and not fully paid).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['approved', 'partially_paid'])
                     ->where('remaining_amount', '>', 0);
    }

    /**
     * Scope for advances applicable to a specific salary type.
     */
    public function scopeApplicableToSalaryType($query, $salaryType)
    {
        return $query->where(function($q) use ($salaryType) {
            $q->whereJsonContains('applicable_salary_types', $salaryType)
              ->orWhereNull('applicable_salary_types');
        });
    }

    /**
     * ===========================================
     * HELPER METHODS
     * ===========================================
     */

    /**
     * Check if advance is fully paid.
     */
    public function isFullyPaid(): bool
    {
        return $this->remaining_amount <= 0 || $this->status === 'fully_paid';
    }

    /**
     * Check if advance can be deducted.
     */
    public function canBeDeducted(): bool
    {
        return in_array($this->status, ['approved', 'partially_paid']) && 
               $this->remaining_amount > 0;
    }

    /**
     * Check if this advance applies to a given salary type.
     */
    public function appliesToSalaryType(string $salaryType): bool
    {
        // If no specific types set, applies to all
        if (empty($this->applicable_salary_types)) {
            return true;
        }
        
        return in_array($salaryType, $this->applicable_salary_types);
    }

    /**
     * Calculate the deduction amount for a given payment.
     */
    public function calculateDeductionAmount(float $paymentAmount, string $salaryType = null): float
    {
        if (!$this->canBeDeducted()) {
            return 0;
        }

        // Check if this advance applies to this salary type
        if ($salaryType && !$this->appliesToSalaryType($salaryType)) {
            return 0;
        }

        // If remaining amount is less than payment amount, deduct all
        if ($this->remaining_amount <= $paymentAmount) {
            return $this->remaining_amount;
        }

        // Otherwise, deduct based on frequency
        switch ($this->deduction_frequency) {
            case 'one_time':
                return $this->remaining_amount; // Deduct all at once
            case 'weekly':
            case 'monthly':
            case 'yearly':
                // Check if we should deduct this period based on deduction day
                if ($this->shouldDeductThisPeriod()) {
                    return min($this->installment_amount ?? $this->advance_amount / ($this->installments ?? 1), $this->remaining_amount);
                }
                return 0;
            default:
                return 0;
        }
    }

    /**
     * Determine if we should deduct in the current period based on deduction day.
     */
    private function shouldDeductThisPeriod(): bool
    {
        if (!$this->deduction_day) {
            return true;
        }

        $today = now();
        
        switch ($this->deduction_frequency) {
            case 'monthly':
                // Deduct on the specified day of the month
                return $today->day == $this->deduction_day;
            case 'weekly':
                // Deduct on the specified day of the week (1-7, Monday=1)
                return $today->dayOfWeekIso == $this->deduction_day;
            case 'yearly':
                // Deduct on the specified day of the year (1-366)
                return $today->dayOfYear == $this->deduction_day;
            default:
                return true;
        }
    }

    /**
     * Record a deduction made against this advance.
     */
    public function recordDeduction(float $amount, EmployeePayment $payment): void
    {
        $history = $this->deduction_history ?? [];
        $history[] = [
            'payment_id' => $payment->id,
            'payment_date' => $payment->payment_date->format('Y-m-d'),
            'payment_type' => $payment->payment_type,
            'deduction_amount' => $amount,
            'remaining_before' => $this->remaining_amount,
            'remaining_after' => $this->remaining_amount - $amount,
            'deducted_at' => now()->toDateTimeString(),
        ];

        $this->remaining_amount -= $amount;
        $this->installments_paid += 1;
        $this->deduction_history = $history;

        if ($this->remaining_amount <= 0) {
            $this->status = 'fully_paid';
            $this->remaining_amount = 0;
        } elseif ($this->status === 'approved') {
            $this->status = 'partially_paid';
        }

        $this->save();
    }

    /**
     * Generate deduction schedule.
     */
    public function generateDeductionSchedule(): array
    {
        $schedule = [];
        $remaining = $this->remaining_amount;
        $installmentCount = $this->installments ?? 1;
        $installmentAmount = $this->installment_amount ?? ($this->advance_amount / $installmentCount);
        
        $startDate = $this->deduction_start_date ? \Carbon\Carbon::parse($this->deduction_start_date) : now();
        
        for ($i = 0; $i < $installmentCount; $i++) {
            if ($remaining <= 0) break;
            
            $deductionAmount = min($installmentAmount, $remaining);
            $deductionDate = $this->calculateNextDeductionDate($startDate, $i);
            
            $schedule[] = [
                'installment' => $i + 1,
                'scheduled_date' => $deductionDate->format('Y-m-d'),
                'amount' => $deductionAmount,
                'status' => 'pending'
            ];
            
            $remaining -= $deductionAmount;
        }
        
        $this->deduction_schedule = $schedule;
        $this->save();
        
        return $schedule;
    }

    /**
     * Calculate next deduction date based on frequency.
     */
    private function calculateNextDeductionDate($startDate, $offset): \Carbon\Carbon
    {
        $date = clone $startDate;
        
        switch ($this->deduction_frequency) {
            case 'weekly':
                return $date->addWeeks($offset);
            case 'monthly':
                return $date->addMonths($offset);
            case 'yearly':
                return $date->addYears($offset);
            default:
                return $date;
        }
    }

    /**
     * Get status badge HTML.
     */
    public function getStatusBadgeAttribute(): string
    {
        $colors = [
            'pending' => 'warning',
            'approved' => 'info',
            'partially_paid' => 'primary',
            'fully_paid' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'secondary',
        ];

        $labels = [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'partially_paid' => 'Partially Paid',
            'fully_paid' => 'Fully Paid',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
        ];

        $color = $colors[$this->status] ?? 'secondary';
        $label = $labels[$this->status] ?? ucfirst($this->status);

        return '<span class="badge badge-light-' . $color . '">' . $label . '</span>';
    }

    /**
     * Get deduction frequency label.
     */
    public function getDeductionFrequencyLabelAttribute(): string
    {
        $labels = [
            'one_time' => 'One Time',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
        ];

        return $labels[$this->deduction_frequency] ?? ucfirst($this->deduction_frequency);
    }

    /**
     * Get progress percentage.
     */
    public function getProgressPercentageAttribute(): int
    {
        if ($this->advance_amount <= 0) return 0;
        
        $paid = $this->advance_amount - $this->remaining_amount;
        return round(($paid / $this->advance_amount) * 100);
    }
}