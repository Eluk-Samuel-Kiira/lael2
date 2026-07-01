<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'app_name',
        'favicon',
        'logo',
        'app_email',
        'app_contact',
        'meta_keyword',
        'meta_descrip',
        'mail_status',
        'mail_mailer',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_address',
        'mail_name',
        'menu_nav_color',
        'font_family',
        'font_size',
        'locale',
        'currency',
        'public_key',
        'private_key',
        'license_type',
        'license_expires_at',
        'license_active',
        'max_users',
        'max_products',
        'max_departments',
        'max_categories',
        'max_suppliers',
        'enable_inventory',
        'enable_multi_location',
        'enable_reports',
        'enable_api',
        'enable_backup',
        'storage_limit_mb',
        'created_by',
    ];

    protected $casts = [
        'license_expires_at' => 'datetime',
        'license_active' => 'boolean',
        'enable_inventory' => 'boolean',
        'enable_multi_location' => 'boolean',
        'enable_reports' => 'boolean',
        'enable_api' => 'boolean',
        'enable_backup' => 'boolean',
    ];

    /**
     * Get the tenant that owns the setting.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who created the setting.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Mutator for private_key - hash it before storing
     */
    public function setPrivateKeyAttribute($value)
    {
        $this->attributes['private_key'] = bcrypt($value);
    }

    /**
     * Check if license is valid
     */
    public function isLicenseValid(): bool
    {
        return $this->license_active && 
               (!$this->license_expires_at || $this->license_expires_at->isFuture());
    }

    /**
     * Generate a new public key
     */
    public static function generatePublicKey(): string
    {
        return 'LAEL-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . 
               strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
    }

    /**
     * Get settings for a specific tenant
     */
    public static function forTenant($tenantId = null)
    {
        $tenantId = $tenantId ?? auth()->user()->tenant_id;
        return self::where('tenant_id', $tenantId)->first();
    }

    /**
     * Get global settings (for super admin)
     */
    public static function global()
    {
        return self::whereNull('tenant_id')->first();
    }

    /**
     * Check if a feature is enabled for this tenant
     */
    public function isFeatureEnabled($feature): bool
    {
        return match($feature) {
            'inventory' => $this->enable_inventory,
            'multi_location' => $this->enable_multi_location,
            'reports' => $this->enable_reports,
            'api' => $this->enable_api,
            'backup' => $this->enable_backup,
            default => false,
        };
    }

    /**
     * Get the resource limit for a specific resource
     */
    public function getLimit($resource): int
    {
        return match($resource) {
            'users' => $this->max_users,
            'products' => $this->max_products,
            'departments' => $this->max_departments,
            'categories' => $this->max_categories,
            'suppliers' => $this->max_suppliers,
            'storage' => $this->storage_limit_mb,
            default => 0,
        };
    }
}