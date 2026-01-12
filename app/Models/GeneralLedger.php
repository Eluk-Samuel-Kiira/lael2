<?php
// app/Models/GeneralLedger.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneralLedger extends Model
{
    use HasFactory;

    protected $table = 'general_ledger';

    protected $fillable = [
        'tenant_id',
        'journal_line_id',
        'account_id',
        'entry_date',
        'period_id',
        'debit_amount',
        'credit_amount',
        'running_balance',
        'description',
        'source_module',
        'source_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'running_balance' => 'decimal:2',
    ];

    // 👇 ACCESSORS - Format for display
    public function getDebitAmountAttribute($value)
    {
        return formatCurrency($value);
    }

    public function getCreditAmountAttribute($value)
    {
        return formatCurrency($value);
    }

    public function getRunningBalanceAttribute($value)
    {
        return formatCurrency($value);
    }

    // 👇 MUTATORS - Convert to USD when saving
    public function setDebitAmountAttribute($value)
    {
        $this->attributes['debit_amount'] = toUSD($value);
    }

    public function setCreditAmountAttribute($value)
    {
        $this->attributes['credit_amount'] = toUSD($value);
    }

    public function setRunningBalanceAttribute($value)
    {
        $this->attributes['running_balance'] = toUSD($value);
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function journalLine(): BelongsTo
    {
        return $this->belongsTo(JournalEntryLine::class, 'journal_line_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }

    // Scopes
    public function scopeByAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('entry_date', [$startDate, $endDate]);
    }

    public function scopeDebits($query)
    {
        return $query->where('debit_amount', '>', 0);
    }

    public function scopeCredits($query)
    {
        return $query->where('credit_amount', '>', 0);
    }

    public function scopeBySource($query, $module, $sourceId)
    {
        return $query->where('source_module', $module)
            ->where('source_id', $sourceId);
    }

    // Methods
    public function getAmountAttribute(): float
    {
        return $this->isDebit() ? $this->attributes['debit_amount'] : $this->attributes['credit_amount'];
    }

    public function isDebit(): bool
    {
        return ($this->attributes['debit_amount'] ?? 0) > 0;
    }

    public function isCredit(): bool
    {
        return ($this->attributes['credit_amount'] ?? 0) > 0;
    }

    public function getTransactionTypeAttribute(): string
    {
        return $this->isDebit() ? 'Debit' : 'Credit';
    }

    /**
     * Get the raw debit amount (for calculations)
     */
    public function getRawDebitAmount(): float
    {
        return $this->attributes['debit_amount'] ?? 0;
    }

    /**
     * Get the raw credit amount (for calculations)
     */
    public function getRawCreditAmount(): float
    {
        return $this->attributes['credit_amount'] ?? 0;
    }

    /**
     * Get the raw running balance (for calculations)
     */
    public function getRawRunningBalance(): float
    {
        return $this->attributes['running_balance'] ?? 0;
    }
}