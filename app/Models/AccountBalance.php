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
        'opening_balance' => 'integer', // Stored in smallest unit
        'debit_total' => 'integer',      // Stored in smallest unit
        'credit_total' => 'integer',      // Stored in smallest unit
    ];




    /**
     * Accessors - Convert from stored integer to display float
     */
    public function getOpeningBalanceAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getDebitTotalAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getCreditTotalAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    /**
     * Computed closing balance accessor
     */
    public function getClosingBalanceAttribute(): ?float
    {
        $opening = $this->opening_balance ?? 0;
        $debits = $this->debit_total ?? 0;
        $credits = $this->credit_total ?? 0;
        
        // For asset/expense accounts: opening + debits - credits
        // For liability/equity/revenue accounts: opening + credits - debits
        // You might want to adjust based on account type
        if ($this->account && in_array($this->account->type, ['asset', 'expense'])) {
            return $opening + $debits - $credits;
        }
        
        return $opening + $credits - $debits;
    }

    /**
     * Mutators - Convert from display float to stored integer
     */
    public function setOpeningBalanceAttribute($value): void
    {
        $this->attributes['opening_balance'] = to_base_currency($value);
    }

    public function setDebitTotalAttribute($value): void
    {
        $this->attributes['debit_total'] = to_base_currency($value);
    }

    public function setCreditTotalAttribute($value): void
    {
        $this->attributes['credit_total'] = to_base_currency($value);
    }

    /**
     * Helper method to update balances based on a transaction
     */
    public static function updateBalance(
        int $accountId,
        int $periodId,
        string $date,
        float $debitAmount = 0,
        float $creditAmount = 0
    ): self {
        $tenantId = current_tenant_id();
        
        $balance = self::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'account_id' => $accountId,
                'period_id' => $periodId,
                'balance_date' => $date,
            ],
            [
                'opening_balance' => 0,
                'debit_total' => 0,
                'credit_total' => 0,
            ]
        );

        $balance->debit_total = ($balance->debit_total ?? 0) + $debitAmount;
        $balance->credit_total = ($balance->credit_total ?? 0) + $creditAmount;
        $balance->save();

        return $balance;
    }

    /**
     * Scope for date range
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('balance_date', [$startDate, $endDate]);
    }

    /**
     * Get balances in raw integer form (for calculations)
     */
    public function getRawOpeningBalance(): ?int
    {
        return $this->attributes['opening_balance'] ?? null;
    }

    public function getRawDebitTotal(): ?int
    {
        return $this->attributes['debit_total'] ?? null;
    }

    public function getRawCreditTotal(): ?int
    {
        return $this->attributes['credit_total'] ?? null;
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
        $netChange = abs($this->getRawNetChange());
        $currencyCode = $this->getCurrencyCode();
        return format_currency($netChange, $currencyCode);
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