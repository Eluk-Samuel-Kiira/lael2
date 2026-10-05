<?php
// app/Models/ExpenseTemplate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasTenant;

class ExpenseTemplate extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id', 'name', 'code', 'description',
        'category_id', 'supplier_id', 'department_id', 'location_id',
        'employee_id', 'payment_method_id',
        'default_amount', 'last_amount',
        'frequency', 'reminder_interval_days',
        'requires_receipt', 'requires_approval',
        'default_tax_ids',
        'usage_count', 'last_used_at',
        'is_active', 'created_by',
    ];

    protected $casts = [
        'default_tax_ids' => 'array',
        'requires_receipt' => 'boolean',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
        'default_amount' => 'integer',
        'last_amount' => 'integer',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
    ];

    // Display-currency accessors
    public function getDefaultAmountAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }
    public function setDefaultAmountAttribute($v): void
    {
        $this->attributes['default_amount'] = $v === null ? null : to_base_currency($v);
    }
    public function getLastAmountAttribute($v): ?float
    {
        return $v === null ? null : from_base_currency($v);
    }
    public function setLastAmountAttribute($v): void
    {
        $this->attributes['last_amount'] = $v === null ? null : to_base_currency($v);
    }

    // Relationships
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function category() { return $this->belongsTo(ExpenseCategory::class, 'category_id'); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function location() { return $this->belongsTo(Location::class); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function paymentMethod() { return $this->belongsTo(PaymentMethod::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'template_id');
    }

    // Scopes
    public function scopeActive($q) { return $q->where('is_active', true); }

    // Helpers
    public function suggestedAmount(): ?float
    {
        return $this->last_amount ?? $this->default_amount;
    }

    public function markUsed(float $amount): void
    {
        $this->update([
            'last_amount'  => $amount,
            'usage_count'  => $this->usage_count + 1,
            'last_used_at' => now(),
        ]);
    }
}