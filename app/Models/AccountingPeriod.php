<?php
// app/Models/AccountingPeriod.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingPeriod extends Model
{
    use HasFactory;

    protected $table = 'accounting_periods';
    
    protected $fillable = [
        'tenant_id',
        'period_name',
        'start_date',
        'end_date',
        'status',
        'closed_by',
        'closed_at',
        'is_fiscal_year_end',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
        'is_fiscal_year_end' => 'boolean',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'period_id');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeCurrent($query)
    {
        return $query->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where('status', 'open');
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date);
    }

    // Methods
    public function close($userId): bool
    {
        if ($this->status === 'closed') {
            return false;
        }

        $this->update([
            'status' => 'closed',
            'closed_by' => $userId,
            'closed_at' => now(),
        ]);

        return true;
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    public function containsDate($date): bool
    {
        $date = $date instanceof \DateTime ? $date : new \DateTime($date);
        return $date >= $this->start_date && $date <= $this->end_date;
    }
}