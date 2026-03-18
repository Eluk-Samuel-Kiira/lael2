<?php
// app/Models/TaxLiability.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxLiability extends Model
{
    use HasFactory, SoftDeletes;

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
        'amount' => 'decimal:2',
        'rate' => 'decimal:2',
        'due_date' => 'date',
        'remitted_at' => 'datetime',
        'metadata' => 'array',
        'tax_year' => 'integer',
        'tax_month' => 'integer',
        'tax_quarter' => 'integer',
    ];

    /**
     * Relationships
     */
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

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRemitted($query)
    {
        return $query->where('status', 'remitted');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
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

    public function scopeForPayment($query, $paymentId)
    {
        return $query->where('employee_payment_id', $paymentId);
    }

    /**
     * Accessors
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending' => '<span class="badge badge-light-warning">Pending</span>',
            'remitted' => '<span class="badge badge-light-success">Remitted</span>',
            'overdue' => '<span class="badge badge-light-danger">Overdue</span>',
            'cancelled' => '<span class="badge badge-light-secondary">Cancelled</span>',
            default => '<span class="badge badge-light-info">' . ucfirst($this->status) . '</span>',
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . ($this->tenant->currency ?? 'UGX');
    }

    public function getPeriodAttribute(): string
    {
        $period = $this->tax_year;
        
        if ($this->tax_month) {
            $monthName = \Carbon\Carbon::create()->month($this->tax_month)->format('F');
            $period .= ' - ' . $monthName;
        } elseif ($this->tax_quarter) {
            $period .= ' - Q' . $this->tax_quarter;
        }
        
        return $period;
    }

    /**
     * Methods
     */
    public function markAsRemitted($remittanceData = [])
    {
        $this->update(array_merge([
            'status' => 'remitted',
            'remitted_at' => now(),
        ], $remittanceData));
    }

    public function markAsOverdue()
    {
        if ($this->status === 'pending' && $this->due_date && $this->due_date->isPast()) {
            $this->update(['status' => 'overdue']);
        }
    }

    /**
     * Static Helpers
     */
    public static function getPendingTotal($tenantId)
    {
        return self::where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('amount');
    }

    public static function getMonthlyTotal($tenantId, $year, $month)
    {
        return self::where('tenant_id', $tenantId)
            ->where('tax_year', $year)
            ->where('tax_month', $month)
            ->where('status', 'remitted')
            ->sum('amount');
    }

    public static function getOutstandingByPeriod($tenantId)
    {
        return self::where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'overdue'])
            ->selectRaw('tax_year, tax_month, tax_quarter, SUM(amount) as total')
            ->groupBy('tax_year', 'tax_month', 'tax_quarter')
            ->get();
    }
}