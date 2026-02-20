<?php
// app/Models/JournalEntryLine.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntryLine extends Model
{
    use HasFactory;

    protected $table = 'journal_entry_lines';

    protected $fillable = [
        'journal_id',
        'account_id',
        'line_number',
        'description',
        'debit_amount',
        'credit_amount',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        // Money fields - stored as integers in DB
        'debit_amount' => 'integer',
        'credit_amount' => 'integer',
    ];

    /**
     * Accessors - Convert from stored integer to display float
     */
    public function getDebitAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getCreditAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    /**
     * Mutators - Convert from display float to stored integer
     */
    public function setDebitAmountAttribute($value): void
    {
        $this->attributes['debit_amount'] = to_base_currency($value);
    }

    public function setCreditAmountAttribute($value): void
    {
        $this->attributes['credit_amount'] = to_base_currency($value);
    }

    


    // Relationships
    public function journal(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function generalLedger()
    {
        return $this->hasOne(GeneralLedger::class, 'journal_line_id');
    }

    // Methods
    public function getAmountAttribute(): string
    {
        $debit = $this->attributes['debit_amount'] ?? 0;
        $credit = $this->attributes['credit_amount'] ?? 0;
        
        return formatCurrency($debit > 0 ? $debit : $credit);
    }

    public function getRawAmount(): float
    {
        $debit = $this->attributes['debit_amount'] ?? 0;
        $credit = $this->attributes['credit_amount'] ?? 0;
        
        return $debit > 0 ? $debit : $credit;
    }

    public function isDebit(): bool
    {
        return ($this->attributes['debit_amount'] ?? 0) > 0;
    }

    public function isCredit(): bool
    {
        return ($this->attributes['credit_amount'] ?? 0) > 0;
    }

    /**
     * Get raw debit amount (bypass accessor)
     */
    public function getRawDebitAmount(): float
    {
        return (float) ($this->attributes['debit_amount'] ?? 0);
    }

    /**
     * Get raw credit amount (bypass accessor)
     */
    public function getRawCreditAmount(): float
    {
        return (float) ($this->attributes['credit_amount'] ?? 0);
    }

    /**
     * Get transaction type
     */
    public function getTransactionTypeAttribute(): string
    {
        return $this->isDebit() ? 'Debit' : 'Credit';
    }

    /**
     * Validate line entry
     */
    public function validate(): bool
    {
        $debit = $this->getRawDebitAmount();
        $credit = $this->getRawCreditAmount();
        
        // Must have either debit OR credit, not both, not none
        if (($debit > 0 && $credit > 0) || ($debit == 0 && $credit == 0)) {
            throw new \Exception('Journal entry line must have either debit OR credit amount, not both or none.');
        }
        
        // Amounts must be positive
        if ($debit < 0 || $credit < 0) {
            throw new \Exception('Journal entry amounts must be positive.');
        }
        
        return true;
    }

    // Model Events
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($line) {
            // Auto-validate before saving
            $line->validate();
            
            // Update parent journal totals
            if ($line->journal) {
                $line->journal->calculateBalance();
            }
        });
    }
}