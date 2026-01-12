<?php
// app/Models/AccountBalance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountBalance extends Model
{
    use HasFactory;

    protected $table = 'account_balances';

    protected $fillable = [
        'tenant_id',
        'account_id',
        'period_id',
        'balance_date',
        'opening_balance',
        'debit_total',
        'credit_total',
    ];

    protected $casts = [
        'balance_date' => 'date',
        'opening_balance' => 'decimal:2',
        'debit_total' => 'decimal:2',
        'credit_total' => 'decimal:2',
    ];

    // 👇 ACCESSORS - Format for display
    public function getOpeningBalanceAttribute($value)
    {
        return formatCurrency($value);
    }

    public function getDebitTotalAttribute($value)
    {
        return formatCurrency($value);
    }

    public function getCreditTotalAttribute($value)
    {
        return formatCurrency($value);
    }

    public function getClosingBalanceAttribute()
    {
        // Calculate closing balance
        $openingBalance = $this->attributes['opening_balance'] ?? 0;
        $debitTotal = $this->attributes['debit_total'] ?? 0;
        $creditTotal = $this->attributes['credit_total'] ?? 0;
        
        $account = $this->account;
        if ($account && $account->normal_balance === 'D') {
            $closingBalance = $openingBalance + $debitTotal - $creditTotal;
        } else {
            $closingBalance = $openingBalance + $creditTotal - $debitTotal;
        }
        
        return formatCurrency($closingBalance);
    }

    // 👇 MUTATORS - Convert to USD when saving
    public function setOpeningBalanceAttribute($value)
    {
        $this->attributes['opening_balance'] = toUSD($value);
    }

    public function setDebitTotalAttribute($value)
    {
        $this->attributes['debit_total'] = toUSD($value);
    }

    public function setCreditTotalAttribute($value)
    {
        $this->attributes['credit_total'] = toUSD($value);
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
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
    public function scopeAsOf($query, $date)
    {
        return $query->where('balance_date', '<=', $date)
            ->orderBy('balance_date', 'desc');
    }

    public function scopeByPeriod($query, $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    public function scopeByAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    // Methods

    /**
     * Calculate closing balance (raw calculation for internal use)
     */
    public function calculateClosingBalance(): float
    {
        $openingBalance = $this->attributes['opening_balance'] ?? 0;
        $debitTotal = $this->attributes['debit_total'] ?? 0;
        $creditTotal = $this->attributes['credit_total'] ?? 0;
        
        $account = $this->account;
        if ($account && $account->normal_balance === 'D') {
            return $openingBalance + $debitTotal - $creditTotal;
        } else {
            return $openingBalance + $creditTotal - $debitTotal;
        }
    }

    /**
     * Get raw closing balance (for calculations)
     */
    public function getRawClosingBalance(): float
    {
        return $this->calculateClosingBalance();
    }

    /**
     * Get raw opening balance (for calculations)
     */
    public function getRawOpeningBalance(): float
    {
        return $this->attributes['opening_balance'] ?? 0;
    }

    /**
     * Get raw debit total (for calculations)
     */
    public function getRawDebitTotal(): float
    {
        return $this->attributes['debit_total'] ?? 0;
    }

    /**
     * Get raw credit total (for calculations)
     */
    public function getRawCreditTotal(): float
    {
        return $this->attributes['credit_total'] ?? 0;
    }

    /**
     * Get net change (difference between debits and credits)
     */
    public function getNetChange(): float
    {
        $debitTotal = $this->attributes['debit_total'] ?? 0;
        $creditTotal = $this->attributes['credit_total'] ?? 0;
        $account = $this->account;
        
        if ($account && $account->normal_balance === 'D') {
            return $debitTotal - $creditTotal;
        } else {
            return $creditTotal - $debitTotal;
        }
    }

    /**
     * Get formatted net change
     */
    public function getFormattedNetChangeAttribute()
    {
        return formatCurrency(abs($this->getNetChange()));
    }

    /**
     * Update balances with new transaction
     */
    public function updateWithTransaction(float $debit, float $credit): bool
    {
        $debitTotal = $this->attributes['debit_total'] ?? 0;
        $creditTotal = $this->attributes['credit_total'] ?? 0;
        
        $this->update([
            'debit_total' => $debitTotal + $debit,
            'credit_total' => $creditTotal + $credit,
        ]);
        
        return true;
    }

    /**
     * Check if balance is for asset/expense account
     */
    public function isDebitNormalBalance(): bool
    {
        return $this->account && $this->account->normal_balance === 'D';
    }

    /**
     * Check if balance is for liability/equity/revenue account
     */
    public function isCreditNormalBalance(): bool
    {
        return $this->account && $this->account->normal_balance === 'C';
    }

    /**
     * Recalculate and save the balance
     */
    public function recalculate(): void
    {
        $this->save(); // Will trigger accessors/mutators
    }

    /**
     * Create opening balance for a new period
     */
    public static function createOpeningBalance(
        int $tenantId,
        int $accountId,
        int $periodId,
        float $openingBalance = 0,
        $balanceDate = null
    ): self {
        $balanceDate = $balanceDate ?? now()->toDateString();
        
        return self::create([
            'tenant_id' => $tenantId,
            'account_id' => $accountId,
            'period_id' => $periodId,
            'balance_date' => $balanceDate,
            'opening_balance' => $openingBalance,
            'debit_total' => 0,
            'credit_total' => 0,
        ]);
    }
}