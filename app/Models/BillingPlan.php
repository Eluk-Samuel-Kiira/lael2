<?php
// app/Models/BillingPlan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillingPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'billing_plans';
    protected $primaryKey = 'plan_id';

    protected $fillable = [
        'plan_code',
        'plan_name',
        'description',
        'monthly_price',
        'annual_price',
        'onetime_fee',
        'setup_fee',
        'currency',
        'default_shops',
        'default_locations',
        'default_departments',
        'default_users',
        'default_products',
        'default_customers',
        'default_suppliers',
        'default_storage_gb',
        'includes_inventory',
        'includes_accounting',
        'includes_hr_payroll',
        'includes_multicurrency',
        'includes_advanced_reports',
        'includes_api_access',
        'includes_ecommerce',
        'includes_pos',
        'includes_crm',
        'includes_support_priority',
        'includes_custom_branding',
        'trial_days',
        'billing_cycle_days',
        'is_public',
        'is_active',
        'sort_order',
        'features_list',
        'limitations',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'annual_price' => 'decimal:2',
        'onetime_fee' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'includes_inventory' => 'boolean',
        'includes_accounting' => 'boolean',
        'includes_hr_payroll' => 'boolean',
        'includes_multicurrency' => 'boolean',
        'includes_advanced_reports' => 'boolean',
        'includes_api_access' => 'boolean',
        'includes_ecommerce' => 'boolean',
        'includes_pos' => 'boolean',
        'includes_crm' => 'boolean',
        'includes_support_priority' => 'boolean',
        'includes_custom_branding' => 'boolean',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'features_list' => 'array',
        'limitations' => 'array',
    ];

    // ==================== ACCESSORS (READ) ====================
    
    /**
     * Format monthly price with currency symbol
     */
    public function getMonthlyPriceAttribute($value)
    {
        if (!function_exists('formatCurrency')) {
            return number_format($value, 2);
        }
        return formatCurrency($value, $this->currency);
    }

    /**
     * Get raw monthly price without formatting
     */
    public function getMonthlyPriceRawAttribute()
    {
        return $this->attributes['monthly_price'] ?? 0;
    }

    /**
     * Format annual price with currency symbol
     */
    public function getAnnualPriceAttribute($value)
    {
        if (!function_exists('formatCurrency')) {
            return number_format($value, 2);
        }
        return formatCurrency($value, $this->currency);
    }

    /**
     * Get raw annual price without formatting
     */
    public function getAnnualPriceRawAttribute()
    {
        return $this->attributes['annual_price'] ?? 0;
    }

    /**
     * Format one-time fee with currency symbol
     */
    public function getOnetimeFeeAttribute($value)
    {
        if (!function_exists('formatCurrency')) {
            return number_format($value, 2);
        }
        return formatCurrency($value, $this->currency);
    }

    /**
     * Get raw one-time fee without formatting
     */
    public function getOnetimeFeeRawAttribute()
    {
        return $this->attributes['onetime_fee'] ?? 0;
    }

    /**
     * Format setup fee with currency symbol
     */
    public function getSetupFeeAttribute($value)
    {
        if (!function_exists('formatCurrency')) {
            return number_format($value, 2);
        }
        return formatCurrency($value, $this->currency);
    }

    /**
     * Get raw setup fee without formatting
     */
    public function getSetupFeeRawAttribute()
    {
        return $this->attributes['setup_fee'] ?? 0;
    }

    /**
     * Get total first payment (setup + first month/annual)
     */
    public function getFirstPaymentTotalAttribute()
    {
        $firstPayment = $this->attributes['setup_fee'] ?? 0;
        if ($this->attributes['annual_price'] > 0) {
            $firstPayment += $this->attributes['annual_price'];
        } else {
            $firstPayment += $this->attributes['monthly_price'] ?? 0;
        }
        return $firstPayment;
    }

    /**
     * Get formatted first payment total
     */
    public function getFirstPaymentTotalFormattedAttribute()
    {
        $amount = $this->first_payment_total;
        if (!function_exists('formatCurrency')) {
            return number_format($amount, 2);
        }
        return formatCurrency($amount, $this->currency);
    }

    // ==================== MUTATORS (WRITE) ====================

    /**
     * Store monthly price in database as decimal
     */
    public function setMonthlyPriceAttribute($value)
    {
        if (!function_exists('toUSD')) {
            $this->attributes['monthly_price'] = (float) $value;
        } else {
            $this->attributes['monthly_price'] = toUSD($value);
        }
    }

    /**
     * Store annual price in database as decimal
     */
    public function setAnnualPriceAttribute($value)
    {
        if (!function_exists('toUSD')) {
            $this->attributes['annual_price'] = (float) $value;
        } else {
            $this->attributes['annual_price'] = toUSD($value);
        }
    }

    /**
     * Store one-time fee in database as decimal
     */
    public function setOnetimeFeeAttribute($value)
    {
        if (!function_exists('toUSD')) {
            $this->attributes['onetime_fee'] = (float) $value;
        } else {
            $this->attributes['onetime_fee'] = toUSD($value);
        }
    }

    /**
     * Store setup fee in database as decimal
     */
    public function setSetupFeeAttribute($value)
    {
        if (!function_exists('toUSD')) {
            $this->attributes['setup_fee'] = (float) $value;
        } else {
            $this->attributes['setup_fee'] = toUSD($value);
        }
    }

    /**
     * Set currency and convert prices if needed
     */
    public function setCurrencyAttribute($value)
    {
        $oldCurrency = $this->attributes['currency'] ?? 'USD';
        $newCurrency = strtoupper($value);
        
        if ($oldCurrency !== $newCurrency && function_exists('convertCurrency')) {
            // Convert all prices to new currency
            $conversionRate = $this->getConversionRate($oldCurrency, $newCurrency);
            
            if ($conversionRate !== 1.0) {
                $this->attributes['monthly_price'] *= $conversionRate;
                $this->attributes['annual_price'] *= $conversionRate;
                $this->attributes['onetime_fee'] *= $conversionRate;
                $this->attributes['setup_fee'] *= $conversionRate;
            }
        }
        
        $this->attributes['currency'] = $newCurrency;
    }

    // ==================== SCOPES ====================
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeWithTrial($query)
    {
        return $query->where('trial_days', '>', 0);
    }

    public function scopeOneTimeOnly($query)
    {
        return $query->where('monthly_price', 0)
                    ->where('annual_price', 0)
                    ->where('onetime_fee', '>', 0);
    }

    public function scopeSubscription($query)
    {
        return $query->where(function($q) {
            $q->where('monthly_price', '>', 0)
              ->orWhere('annual_price', '>', 0);
        });
    }

    public function scopeFree($query)
    {
        return $query->where('monthly_price', 0)
                    ->where('annual_price', 0)
                    ->where('onetime_fee', 0)
                    ->where('setup_fee', 0);
    }

    // ==================== METHODS ====================

    /**
     * Calculate yearly savings when choosing annual plan
     */
    public function getYearlySavings(): float
    {
        $monthlyTotal = $this->attributes['monthly_price'] * 12;
        $annualPrice = $this->attributes['annual_price'];
        
        if ($annualPrice <= 0 || $monthlyTotal <= 0) {
            return 0;
        }
        
        return $monthlyTotal - $annualPrice;
    }

    /**
     * Get yearly savings percentage
     */
    public function getYearlySavingsPercentage(): float
    {
        $monthlyTotal = $this->attributes['monthly_price'] * 12;
        $annualPrice = $this->attributes['annual_price'];
        
        if ($annualPrice <= 0 || $monthlyTotal <= 0) {
            return 0;
        }
        
        return (($monthlyTotal - $annualPrice) / $monthlyTotal) * 100;
    }

    /**
     * Get effective monthly price (annual/12)
     */
    public function getEffectiveMonthlyPrice(): float
    {
        $annualPrice = $this->attributes['annual_price'];
        if ($annualPrice > 0) {
            return $annualPrice / 12;
        }
        
        return $this->attributes['monthly_price'] ?? 0;
    }

    /**
     * Get all features as array
     */
    public function getFeatures(): array
    {
        $features = [];
        
        $featureMap = [
            'includes_inventory' => 'Inventory Management',
            'includes_accounting' => 'Accounting',
            'includes_hr_payroll' => 'HR & Payroll',
            'includes_multicurrency' => 'Multi-currency',
            'includes_advanced_reports' => 'Advanced Reports',
            'includes_api_access' => 'API Access',
            'includes_ecommerce' => 'E-commerce Integration',
            'includes_pos' => 'Point of Sale',
            'includes_crm' => 'Customer Relationship Management',
            'includes_support_priority' => 'Priority Support',
            'includes_custom_branding' => 'Custom Branding',
        ];
        
        foreach ($featureMap as $field => $label) {
            if ($this->{$field}) {
                $features[] = $label;
            }
        }
        
        return $features;
    }

    /**
     * Check if plan includes a specific feature
     */
    public function hasFeature(string $feature): bool
    {
        $featureField = 'includes_' . strtolower($feature);
        return $this->{$featureField} ?? false;
    }

    /**
     * Activate the plan
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the plan
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Get plan by code
     */
    public static function getPlanByCode(string $code): ?self
    {
        return self::where('plan_code', $code)->first();
    }

    /**
     * Get all active public plans
     */
    public static function getPublicPlans()
    {
        return self::active()->public()->orderBy('sort_order')->get();
    }

    /**
     * Get currency conversion rate (simplified)
     */
    private function getConversionRate(string $from, string $to): float
    {
        // In a real app, you'd fetch this from a currency API
        $rates = [
            'USD' => 1.0,
            'EUR' => 0.85,
            'GBP' => 0.73,
            'CAD' => 1.25,
            'AUD' => 1.35,
            'JPY' => 110.0,
        ];
        
        $fromRate = $rates[$from] ?? 1.0;
        $toRate = $rates[$to] ?? 1.0;
        
        return $toRate / $fromRate;
    }
}