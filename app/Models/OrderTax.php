<?php
// app/Models/OrderTax.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderTax extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'tax_name',
        'tax_rate',
        'tax_amount',
        'is_compound',
        'created_by',
        // ── Remittance fields ──────────────────────
        'tenant_id',
        'status',
        'due_date',
        'tax_year',
        'tax_month',
        'tax_quarter',
        'remitted_at',
        'remittance_reference',
        'remittance_transaction_ref',
        'remittance_payment_method_id',
        'remitted_by',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tax_rate'   => 'decimal:2',
        'tax_amount' => 'integer',
        'is_compound' => 'boolean',
        // ── Remittance casts ───────────────────────
        'due_date'    => 'date',
        'remitted_at' => 'datetime',
        'tax_year'    => 'integer',
        'tax_month'   => 'integer',
        'tax_quarter' => 'integer',
        'metadata'    => 'array',
    ];

    // ── Accessors ──────────────────────────────────────────────────────────

    public function getTaxAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    // ── Mutators ───────────────────────────────────────────────────────────

    public function setTaxAmountAttribute($value): void
    {
        $this->attributes['tax_amount'] = to_base_currency($value);
    }

    // ── Relationships ──────────────────────────────────────────────────────

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderCreater()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function remittedBy()
    {
        return $this->belongsTo(User::class, 'remitted_by');
    }

    public function remittancePaymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'remittance_payment_method_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

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
        if ($month)   $query->where('tax_month', $month);
        if ($quarter) $query->where('tax_quarter', $quarter);
        return $query;
    }

    // ── Methods ────────────────────────────────────────────────────────────

    public function markAsRemitted(array $remittanceData = []): void
    {
        $this->update(array_merge([
            'status'      => 'remitted',
            'remitted_at' => now(),
        ], $remittanceData));
    }

    public function markAsOverdue(): void
    {
        if ($this->status === 'pending' && $this->due_date?->isPast()) {
            $this->update(['status' => 'overdue']);
        }
    }

    public function getPeriodAttribute(): string
    {
        $period = (string) $this->tax_year;
        if ($this->tax_month) {
            $period .= ' - ' . \Carbon\Carbon::create()->month($this->tax_month)->format('F');
        } elseif ($this->tax_quarter) {
            $period .= ' - Q' . $this->tax_quarter;
        }
        return $period;
    }

    // ── Static Helpers ─────────────────────────────────────────────────────

    public static function getPendingTotal(int $tenantId): float
    {
        return self::where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('tax_amount') / 100; // from base currency
    }

    public static function getMonthlyCollected(int $tenantId, int $year, int $month): float
    {
        return self::where('tenant_id', $tenantId)
            ->where('tax_year', $year)
            ->where('tax_month', $month)
            ->sum('tax_amount') / 100;
    }

    public static function getMonthlyRemitted(int $tenantId, int $year, int $month): float
    {
        return self::where('tenant_id', $tenantId)
            ->where('tax_year', $year)
            ->where('tax_month', $month)
            ->where('status', 'remitted')
            ->sum('tax_amount') / 100;
    }

    public static function getOutstandingByPeriod(int $tenantId)
    {
        return self::where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'overdue'])
            ->selectRaw('tax_year, tax_month, tax_quarter, tax_name, SUM(tax_amount) as total')
            ->groupBy('tax_year', 'tax_month', 'tax_quarter', 'tax_name')
            ->get();
    }
}