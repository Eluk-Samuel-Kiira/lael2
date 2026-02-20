<?php
// app/Models/JournalEntry.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class JournalEntry extends Model
{
    use HasFactory;

    protected $table = 'journal_entries';

    protected $fillable = [
        'tenant_id',
        'entry_number',
        'entry_date',
        'period_id',
        'description',
        'reference_number',
        'source_module',
        'source_id',
        'total_debit',
        'total_credit',
        'status',
        'posted_at',
        'posted_by',
        'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'posted_at' => 'datetime',
        'total_debit' => 'integer',
        'total_credit' => 'integer',
        'is_balanced' => 'boolean',
    ];


    /**
     * Accessors - Convert from stored integer to display float
     */
    public function getTotalDebitAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    public function getTotalCreditAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    /**
     * Computed attribute - Check if journal entry is balanced
     */
    public function getIsBalancedAttribute(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }

    /**
     * Mutators - Convert from display float to stored integer
     */
    public function setTotalDebitAttribute($value): void
    {
        $this->attributes['total_debit'] = to_base_currency($value);
    }

    public function setTotalCreditAttribute($value): void
    {
        $this->attributes['total_credit'] = to_base_currency($value);
    }


    // Model Events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($journal) {
            if (empty($journal->entry_number)) {
                $journal->entry_number = static::generateEntryNumber($journal->tenant_id);
            }
        });

        // Auto-calculate totals when saving
        static::saving(function ($journal) {
            $journal->calculateBalance();
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'journal_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeVoid($query)
    {
        return $query->where('status', 'void');
    }

    public function scopeByDate($query, $startDate, $endDate = null)
    {
        $endDate = $endDate ?? $startDate;
        return $query->whereBetween('entry_date', [$startDate, $endDate]);
    }

    public function scopeByPeriod($query, $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    // Methods
    public static function generateEntryNumber($tenantId): string
    {
        $year = date('Y');
        $prefix = "JE-{$year}-";
        
        $lastEntry = static::where('tenant_id', $tenantId)
            ->where('entry_number', 'like', $prefix . '%')
            ->orderBy('entry_number', 'desc')
            ->first();

        if ($lastEntry) {
            $lastNumber = (int) Str::after($lastEntry->entry_number, $prefix);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return $prefix . $nextNumber;
    }



    /**
     * Get balance difference (for debugging)
     */
    public function getBalanceDifferenceAttribute()
    {
        $totalDebit = $this->attributes['total_debit'] ?? 0;
        $totalCredit = $this->attributes['total_credit'] ?? 0;
        return $totalDebit - $totalCredit;
    }

    /**
     * Calculate and update totals from lines
     */
    public function calculateBalance(): void
    {
        if ($this->lines->isNotEmpty()) {
            $totalDebit = 0;
            $totalCredit = 0;
            
            foreach ($this->lines as $line) {
                // Use raw values from lines
                $totalDebit += $line->getRawOriginal('debit_amount') ?? 0;
                $totalCredit += $line->getRawOriginal('credit_amount') ?? 0;
            }
            
            $this->attributes['total_debit'] = $totalDebit;
            $this->attributes['total_credit'] = $totalCredit;
        }
    }

    /**
     * Post the journal entry
     */
    public function post($userId): bool
    {
        if ($this->status !== 'draft') {
            return false;
        }

        if (!$this->is_balanced) {
            throw new \Exception('Journal entry must be balanced before posting.');
        }

        $this->update([
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => $userId,
        ]);

        // Post to General Ledger
        $this->postToGeneralLedger();

        return true;
    }

    /**
     * Void the journal entry
     */
    public function void($userId): bool
    {
        if ($this->status !== 'posted') {
            return false;
        }

        // Create reversing entry
        $reversingEntry = $this->replicate();
        $reversingEntry->entry_number = static::generateEntryNumber($this->tenant_id);
        $reversingEntry->description = "Void: " . $this->description;
        $reversingEntry->status = 'posted';
        $reversingEntry->posted_at = now();
        $reversingEntry->posted_by = $userId;
        $reversingEntry->save();

        foreach ($this->lines as $line) {
            $reversingLine = $line->replicate();
            $reversingLine->journal_id = $reversingEntry->id;
            $reversingLine->debit_amount = $line->credit_amount;
            $reversingLine->credit_amount = $line->debit_amount;
            $reversingLine->save();
        }

        $this->update(['status' => 'void']);

        return true;
    }

    /**
     * Post entries to General Ledger
     */
    private function postToGeneralLedger(): void
    {
        foreach ($this->lines as $line) {
            GeneralLedger::create([
                'tenant_id' => $this->tenant_id,
                'journal_line_id' => $line->id,
                'account_id' => $line->account_id,
                'entry_date' => $this->entry_date,
                'period_id' => $this->period_id,
                'debit_amount' => $line->debit_amount,
                'credit_amount' => $line->credit_amount,
                'description' => $line->description ?? $this->description,
                'source_module' => $this->source_module,
                'source_id' => $this->source_id,
            ]);
        }
    }

    /**
     * Format display helper
     */
    public function getFormattedDifferenceAttribute()
    {
        $difference = $this->balance_difference;
        return formatCurrency(abs($difference));
    }
}