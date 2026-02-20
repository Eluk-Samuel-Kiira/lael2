<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    /** @use HasFactory<\Database\Factories\TaxFactory> */
    use HasFactory;
    
    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'rate',
        'type',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Tax type constants
     */
    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED = 'fixed';

    /**
     * Accessors - Convert rate based on tax type
     */
    public function getRateAttribute($value)
    {
        // If it's a fixed tax, convert from stored integer to display float
        if ($this->type === self::TYPE_FIXED) {
            return from_base_currency((int) $value);
        }
        
        // Percentage taxes - stored as decimal in DB (e.g., 18.0000 for 18%)
        return (float) $value;
    }

    /**
     * Mutators - Convert rate based on tax type
     */
    public function setRateAttribute($value): void
    {
        if ($this->type === self::TYPE_FIXED) {
            // Fixed amount - convert currency to smallest unit
            $this->attributes['rate'] = to_base_currency($value);
        } else {
            // Percentage - store as decimal
            $this->attributes['rate'] = (float) $value;
        }
    }

    /**
     * Get raw rate value for calculations (stored value)
     */
    public function getRawRateAttribute()
    {
        return $this->attributes['rate'] ?? 0;
    }

    /**
     * Get rate without formatting (for calculations)
     */
    public function getCalculableRateAttribute()
    {
        if ($this->type === self::TYPE_FIXED) {
            return (float) $this->rate; // Already converted by accessor
        }
        
        return (float) ($this->attributes['rate'] ?? 0) / 100;
    }

    /**
     * Get formatted rate with appropriate symbol
     */
    public function getFormattedRateAttribute(): string
    {
        if ($this->type === self::TYPE_FIXED) {
            return format_currency($this->rate);
        }
        
        return number_format($this->attributes['rate'] ?? 0, 2) . '%';
    }

    /**
     * Get rate for display in lists/tables
     */
    public function getDisplayRateAttribute(): string
    {
        return $this->formatted_rate;
    }

    /**
     * Check if tax is percentage based
     */
    public function isPercentage(): bool
    {
        return $this->type === self::TYPE_PERCENTAGE;
    }

    /**
     * Check if tax is fixed amount
     */
    public function isFixed(): bool
    {
        return $this->type === self::TYPE_FIXED;
    }

    /**
     * Calculate tax amount for a given taxable amount
     */
    public function calculateTax(float $taxableAmount): float
    {
        if ($this->type === self::TYPE_PERCENTAGE) {
            return $taxableAmount * ($this->attributes['rate'] / 100);
        }
        
        // Fixed amount - return the rate (already converted by accessor)
        return $this->rate;
    }

    /**
     * Calculate tax amount and format for display
     */
    public function calculateFormattedTax(float $taxableAmount): string
    {
        $taxAmount = $this->calculateTax($taxableAmount);
        return format_currency($taxAmount);
    }

    /**
     * Scope active taxes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope percentage taxes
     */
    public function scopePercentage($query)
    {
        return $query->where('type', self::TYPE_PERCENTAGE);
    }

    /**
     * Scope fixed taxes
     */
    public function scopeFixed($query)
    {
        return $query->where('type', self::TYPE_FIXED);
    }

    /**
     * Scope by tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Get tax types for dropdown
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_PERCENTAGE => 'Percentage (%)',
            self::TYPE_FIXED => 'Fixed Amount',
        ];
    }

    public function taxCreater()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}