<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasTenant;


class Supplier extends Model
{
    use HasFactory, SoftDeletes, HasTenant;

    protected $fillable = [
        'tenant_id',
        'created_by',
        // Identity
        'name',
        'trading_name',
        'supplier_type',
        'is_active',
        'supplier_code',
        // Contact
        'contact_person',
        'email',
        'phone',
        'phone_secondary',
        'website',
        // Address
        'address',
        'city',
        'state',
        'postal_code',
        'country_code',
        // Tax & Compliance
        'tax_number',
        'is_vat_registered',
        'vat_number',
        'withholding_tax_applicable',
        'withholding_tax_rate',
        'withholding_tax_exemption_ref',
        'withholding_tax_exemption_expiry',
        // Banking
        'bank_name',
        'bank_branch',
        'bank_account_name',
        'bank_account_number',
        'bank_swift_code',
        'mobile_money_number',
        'mobile_money_provider',
        // Payment Terms
        'payment_terms_days',
        'payment_terms_type',
        'preferred_payment_method',
        'credit_limit',
        // Classification
        'category',
        'risk_level',
        'currency_code',
        // Notes
        'notes',
        'metadata',
    ];

    protected $casts = [
        'is_active'                    => 'boolean',
        'is_vat_registered'            => 'boolean',
        'withholding_tax_applicable'   => 'boolean',
        'withholding_tax_rate'         => 'decimal:2',
        'withholding_tax_exemption_expiry' => 'date',
        'credit_limit'                 => 'integer',
        'payment_terms_days'           => 'integer',
        'metadata'                     => 'array',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeWithholdingApplicable($query)
    {
        return $query->where('withholding_tax_applicable', true);
    }

    public function scopeVatRegistered($query)
    {
        return $query->where('is_vat_registered', true);
    }

    // ── Accessors ──────────────────────────────────────────────────────────

    public function getDisplayNameAttribute(): string
    {
        return $this->trading_name ?? $this->name;
    }

    public function getCreditLimitDisplayAttribute(): float
    {
        return from_base_currency($this->credit_limit);
    }

    // Mutator

    public function setCreditLimitDisplayAttribute($value): void
    {
        $this->attributes['credit_limit'] = to_base_currency($value);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /**
     * Whether the WHT exemption certificate is still valid today.
     */
    public function isWhtExemptionValid(): bool
    {
        if (! $this->withholding_tax_exemption_ref) {
            return false;
        }

        if (! $this->withholding_tax_exemption_expiry) {
            return true; // no expiry = indefinite
        }

        return $this->withholding_tax_exemption_expiry->isFuture();
    }

    /**
     * The effective WHT rate to apply when paying this supplier.
     * Returns 0 if exempt or not applicable.
     */
    public function effectiveWhtRate(): float
    {
        if (! $this->withholding_tax_applicable) {
            return 0.0;
        }

        if ($this->isWhtExemptionValid()) {
            return 0.0;
        }

        return (float) $this->withholding_tax_rate;
    }

    /**
     * Calculate the WHT amount to deduct from a given invoice amount.
     */
    public function calculateWht(float $invoiceAmount): float
    {
        return round($invoiceAmount * ($this->effectiveWhtRate() / 100), 2);
    }
}