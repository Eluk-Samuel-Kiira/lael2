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
        'location_id',
        'department_id',
        'category_id',
        'supplier_id', 
        'vendor_name',
        'gross_amount',     
        'tax_amount',
        'net_amount',       
        'total_amount', 
        'payment_method_id',
        'payment_status',
        'paid_date',
        'is_recurring',
        'tax_breakdown',
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
        'gross_amount' => 'integer',
        'tax_amount' => 'integer',
        'net_amount' => 'integer',
        'total_amount' => 'integer',
        'is_recurring' => 'boolean',
        'paid_date' => 'date',
        'next_recurring_date' => 'date',
        'approved_at' => 'datetime',
        'tax_breakdown' => 'array',
    ];


    // Accessors
    public function getGrossAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getTaxAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getNetAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getTotalAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }


    // Mutators
    public function setGrossAmountAttribute($value): void
    {
        $this->attributes['gross_amount'] = to_base_currency($value);
    }

    public function setTaxAmountAttribute($value): void
    {
        $this->attributes['tax_amount'] = to_base_currency($value);
    }

    public function setNetAmountAttribute($value): void
    {
        $this->attributes['net_amount'] = to_base_currency($value);
    }

    public function setTotalAmountAttribute($value): void
    {
        $this->attributes['total_amount'] = to_base_currency($value);
    }



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

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
    
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
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

    // Add relationship
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // Helper to get supplier name
    public function getSupplierNameAttribute()
    {
        if ($this->supplier) {
            return $this->supplier->name;
        }
        return $this->vendor_name ?? 'N/A';
    }

    // Helper to get supplier tax info
    public function getSupplierTaxInfoAttribute()
    {
        if ($this->supplier) {
            return [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
                'tin' => $this->supplier->tax_number,
                'vat_registered' => $this->supplier->is_vat_registered,
                'vat_number' => $this->supplier->vat_number,
                'wht_applicable' => $this->supplier->withholding_tax_applicable,
                'wht_rate' => $this->supplier->withholding_tax_rate,
            ];
        }
        return null;
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