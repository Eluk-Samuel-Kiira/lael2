<?php
// app/Models/ExpenseCategory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseCategory extends Model
{
    use HasFactory;

    protected $table = 'expense_categories';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'gl_account_id',
        'is_active',
        // Add these:
        'requires_receipt',
        'requires_approval',
        'budget_monthly',
        'budget_annual',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_receipt' => 'boolean',
        'requires_approval' => 'boolean',
        'budget_monthly' => 'decimal:2',
        'budget_annual' => 'decimal:2',
    ];


    /**
     * Get the tenant that owns the expense category.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the GL account associated with the expense category.
     */
    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_account_id');
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include categories for a specific tenant.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to order by name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Get the formatted name with code.
     */
    public function getFormattedNameAttribute()
    {
        return "{$this->name} ({$this->code})";
    }

    /**
     * Check if category can be deleted (no expenses associated).
     */
    public function canBeDeleted()
    {
        // Assuming you have an Expense model
        return !\App\Models\Expense::where('expense_category_id', $this->id)->exists();
    }

    /**
     * Activate the category.
     */
    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the category.
     */
    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Toggle active status.
     */
    public function toggleActive()
    {
        $this->update(['is_active' => !$this->is_active]);
    }

    // In ExpenseCategory model
    public function getBudgetUtilizationAttribute()
    {
        if (!$this->budget_monthly) return null;
        
        $monthlyExpense = Expense::where('category_id', $this->id)
            ->whereMonth('date', date('m'))
            ->whereYear('date', date('Y'))
            ->sum('total_amount');
        
        return ($monthlyExpense / $this->budget_monthly) * 100;
    }
}