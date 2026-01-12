<?php
// app/Models/ChartOfAccount.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'id';
    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'tenant_id',
        'account_code',
        'account_name',
        'account_type',
        'normal_balance',
        'is_active',
        'is_system_account',
        'description',
        'parent_account_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system_account' => 'boolean',
    ];

    // 👇 ACCESSORS - Format for display (if you add monetary fields)
    public function getOpeningBalanceAttribute($value)
    {
        return formatCurrency($value);
    }
    
    public function getCurrentBalanceAttribute($value)
    {
        return formatCurrency($value);
    }

    // 👇 MUTATORS - Convert to USD when saving (if you add monetary fields)
    public function setOpeningBalanceAttribute($value)
    {
        $this->attributes['opening_balance'] = toUSD($value);
    }
    
    public function setCurrentBalanceAttribute($value)
    {
        $this->attributes['current_balance'] = toUSD($value);
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }
    
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_account_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_account_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }

    public function generalLedgerEntries(): HasMany
    {
        return $this->hasMany(GeneralLedger::class, 'account_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    public function scopeAssets($query)
    {
        return $query->where('account_type', 'like', 'asset%');
    }

    public function scopeLiabilities($query)
    {
        return $query->where('account_type', 'like', 'liability%');
    }

    public function scopeEquity($query)
    {
        return $query->where('account_type', 'like', 'equity%');
    }

    public function scopeRevenue($query)
    {
        return $query->where('account_type', 'like', 'revenue%');
    }

    public function scopeExpenses($query)
    {
        return $query->where('account_type', 'like', 'expense%');
    }

    // Methods

    /**
     * Calculate current balance based on journal entries
     */
    public function getBalanceAttribute(): float
    {
        // Get raw sums from general ledger (more accurate than journal entries)
        $debits = $this->generalLedgerEntries()->sum('debit_amount');
        $credits = $this->generalLedgerEntries()->sum('credit_amount');
        
        // Access raw values by getting first result
        $debitSum = $debits ?: 0;
        $creditSum = $credits ?: 0;

        if ($this->normal_balance === 'D') {
            return $debitSum - $creditSum;
        }

        return $creditSum - $debitSum;
    }

    /**
     * Get formatted balance for display
     */
    public function getFormattedBalanceAttribute(): string
    {
        return formatCurrency($this->balance);
    }

    /**
     * Get raw debit total (for calculations)
     */
    public function getRawDebitTotal(): float
    {
        return $this->generalLedgerEntries()->sum('debit_amount') ?: 0;
    }

    /**
     * Get raw credit total (for calculations)
     */
    public function getRawCreditTotal(): float
    {
        return $this->generalLedgerEntries()->sum('credit_amount') ?: 0;
    }

    /**
     * Check if account has debit balance
     */
    public function hasDebitBalance(): bool
    {
        return $this->balance > 0 && $this->normal_balance === 'D';
    }

    /**
     * Check if account has credit balance
     */
    public function hasCreditBalance(): bool
    {
        return $this->balance > 0 && $this->normal_balance === 'C';
    }

    /**
     * Get account balance type
     */
    public function getBalanceType(): string
    {
        if ($this->balance == 0) {
            return 'Zero';
        }
        
        if ($this->normal_balance === 'D') {
            return $this->balance > 0 ? 'Debit' : 'Credit';
        } else {
            return $this->balance > 0 ? 'Credit' : 'Debit';
        }
    }

    /**
     * Check if account can be deleted
     */
    public function canBeDeleted(): bool
    {
        return !$this->is_system_account && 
               !$this->children()->exists() && 
               !$this->journalEntries()->exists();
    }

    /**
     * Deactivate account
     */
    public function deactivate(): bool
    {
        if ($this->is_system_account) {
            return false;
        }

        if ($this->balance != 0) {
            return false;
        }

        return $this->update(['is_active' => false]);
    }

    /**
     * Activate account
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Get full account code with parent hierarchy
     */
    public function getFullAccountCodeAttribute(): string
    {
        $codes = [];
        $account = $this;
        
        while ($account) {
            $codes[] = $account->account_code;
            $account = $account->parent;
        }
        
        return implode('.', array_reverse($codes));
    }

    /**
     * Get account name with code for display
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->account_code} - {$this->account_name}";
    }
}