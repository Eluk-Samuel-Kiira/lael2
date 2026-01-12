<?php
// app/Models/Expense.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Expense extends Model
{
    use HasFactory;

    protected $table = 'expenses';

    protected $fillable = [
        'tenant_id',
        'expense_number',
        'date',
        'description',
        'category_id',
        'vendor_name',
        'amount',
        'tax_amount',
        'total_amount',
        'payment_method_id',
        'payment_status',
        'paid_date',
        'is_recurring',
        'recurring_frequency',
        'next_recurring_date',
        'receipt_url',
        'approved_by',
        'approved_at',
        'employee_id',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_recurring' => 'boolean',
        'paid_date' => 'date',
        'next_recurring_date' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'formatted_amount',
        'formatted_tax_amount',
        'formatted_total_amount',
    ];

    /**
     * Get the tenant that owns the expense.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the expense category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    /**
     * Get the payment method for this payment.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    /**
     * Get the user who approved the expense.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the employee who paid/spent.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who created the expense.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // 👇 Accessors for monetary fields
    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => formatCurrency($value),
            set: fn ($value) => toUSD($value),
        );
    }

    protected function taxAmount(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => formatCurrency($value),
            set: fn ($value) => toUSD($value),
        );
    }

    protected function totalAmount(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => formatCurrency($value),
            set: fn ($value) => toUSD($value),
        );
    }

    /**
     * Formatted amount accessor.
     */
    protected function getFormattedAmountAttribute()
    {
        return formatCurrency($this->attributes['amount'] ?? 0);
    }

    /**
     * Formatted tax amount accessor.
     */
    protected function getFormattedTaxAmountAttribute()
    {
        return formatCurrency($this->attributes['tax_amount'] ?? 0);
    }

    /**
     * Formatted total amount accessor.
     */
    protected function getFormattedTotalAmountAttribute()
    {
        return formatCurrency($this->attributes['total_amount'] ?? 0);
    }

    /**
     * Scope a query to only include expenses for a specific tenant.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to only include expenses in a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include expenses by category.
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to only include expenses by payment status.
     */
    public function scopeByPaymentStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    /**
     * Scope a query to only include recurring expenses.
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Scope a query to order by date (descending).
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('date', 'desc');
    }

    /**
     * Mark expense as paid.
     */
    public function markAsPaid($paidDate = null)
    {
        $this->update([
            'payment_status' => 'paid',
            'paid_date' => $paidDate ?? now(),
        ]);
    }

    /**
     * Mark expense as reimbursed.
     */
    public function markAsReimbursed()
    {
        $this->update([
            'payment_status' => 'reimbursed',
        ]);
    }

    /**
     * Approve the expense.
     */
    public function approve($approvedBy)
    {
        $this->update([
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    /**
     * Generate next expense number.
     */
    public static function generateExpenseNumber($tenantId)
    {
        $prefix = 'EXP-' . date('Y') . '-';
        $lastExpense = self::where('tenant_id', $tenantId)
            ->where('expense_number', 'like', $prefix . '%')
            ->orderBy('expense_number', 'desc')
            ->first();

        if ($lastExpense) {
            $lastNumber = intval(substr($lastExpense->expense_number, strlen($prefix)));
            $nextNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '00001';
        }

        return $prefix . $nextNumber;
    }

    /**
     * Check if expense is overdue (pending for more than 30 days).
     */
    public function getIsOverdueAttribute()
    {
        return $this->payment_status === 'pending' && 
               $this->date->diffInDays(now()) > 30;
    }

    /**
     * Get overdue days count.
     */
    public function getOverdueDaysAttribute()
    {
        if ($this->payment_status === 'pending') {
            return $this->date->diffInDays(now());
        }
        return 0;
    }
}