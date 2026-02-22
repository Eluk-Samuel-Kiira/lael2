<?php

// Add this to your helpers.php file or create a new helper file

use App\Models\TenantConfiguration;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

if (!function_exists('tenant_config')) {
    /**
     * Get tenant configuration value for the current or specified tenant
     * 
     * @param string|null $key Specific config key to retrieve (null returns all config)
     * @param mixed $default Default value if config not found
     * @param int|null $tenantId Specific tenant ID (null uses current tenant)
     * @return mixed
     */
    function tenant_config($key = null, $default = null, $tenantId = null)
    {
        // Get tenant ID
        $tenantId = $tenantId ?? current_tenant_id();
        
        if (!$tenantId) {
            return $default;
        }
        
        // Cache key for tenant config
        $cacheKey = "tenant_config.{$tenantId}";
        
        // Get config from cache or database
        $config = Cache::remember($cacheKey, 3600, function () use ($tenantId) {
            return TenantConfiguration::where('tenant_id', $tenantId)->first();
        });
        
        // If no config found, return default
        if (!$config) {
            return $default;
        }
        
        // If no key specified, return entire config as array
        if ($key === null) {
            return $config->toArray();
        }
        
        // Return specific key if it exists
        $key = str_replace('.', '_', $key); // Convert dot notation to underscore if needed
        
        if (isset($config->$key)) {
            return $config->$key;
        }
        
        // Check if it's a relationship method
        if (method_exists($config, $key)) {
            return $config->$key;
        }
        
        return $default;
    }
}

if (!function_exists('current_tenant_id')) {
    /**
     * Get current tenant ID from various sources
     * 
     * @return int|null
     */
    function current_tenant_id()
    {
        // Try to get from auth user
        if (auth()->check() && auth()->user()->tenant_id) {
            return auth()->user()->tenant_id;
        }
        
        // Try to get from session
        if (session()->has('tenant_id')) {
            return session('tenant_id');
        }
        
        // Try to get from request (subdomain, header, etc.)
        if (request()->header('X-Tenant-ID')) {
            return request()->header('X-Tenant-ID');
        }
        
        // Try to get from subdomain
        $subdomain = explode('.', request()->getHost())[0] ?? null;
        if ($subdomain && $subdomain !== 'www') {
            $tenant = Tenant::where('subdomain', $subdomain)->first();
            if ($tenant) {
                return $tenant->id;
            }
        }
        
        return null;
    }
}

if (!function_exists('tenant_currency')) {
    /**
     * Get tenant currency code
     * 
     * @param int|null $tenantId
     * @return string|null
     */
    function tenant_currency($tenantId = null)
    {
        return tenant_config('currency_code', 'USD', $tenantId);
    }
}

if (!function_exists('tenant_timezone')) {
    /**
     * Get tenant timezone
     * 
     * @param int|null $tenantId
     * @return string|null
     */
    function tenant_timezone($tenantId = null)
    {
        return tenant_config('timezone', 'UTC', $tenantId);
    }
}

if (!function_exists('tenant_locale')) {
    /**
     * Get tenant locale
     * 
     * @param int|null $tenantId
     * @return string|null
     */
    function tenant_locale($tenantId = null)
    {
        return tenant_config('locale', 'en', $tenantId);
    }
}

if (!function_exists('tenant_fiscal_year')) {
    /**
     * Get tenant fiscal year start
     * 
     * @param int|null $tenantId
     * @return string|null
     */
    function tenant_fiscal_year($tenantId = null)
    {
        return tenant_config('fiscal_year_start', date('Y-01-01'), $tenantId);
    }
}

if (!function_exists('tenant_tax_method')) {
    /**
     * Get tenant tax calculation method
     * 
     * @param int|null $tenantId
     * @return string|null
     */
    function tenant_tax_method($tenantId = null)
    {
        return tenant_config('tax_calculation_method', 'exclusive', $tenantId);
    }
}

if (!function_exists('tenant_config_all')) {
    /**
     * Get all tenant configuration
     * 
     * @param int|null $tenantId
     * @return array
     */
    function tenant_config_all($tenantId = null)
    {
        return tenant_config(null, [], $tenantId);
    }
}

if (!function_exists('clear_tenant_config_cache')) {
    /**
     * Clear tenant configuration cache
     * 
     * @param int|null $tenantId
     * @return void
     */
    function clear_tenant_config_cache($tenantId = null)
    {
        $tenantId = $tenantId ?? current_tenant_id();
        
        if ($tenantId) {
            Cache::forget("tenant_config.{$tenantId}");
        }
    }
}

function getTenantUserCountAttribute($tenantId)
{
    return DB::table('model_has_roles')
        ->join('users', 'model_has_roles.model_id', '=', 'users.id')
        ->where('model_has_roles.role_id', $this->id)
        ->where('users.tenant_id', $tenantId)
        ->where('model_has_roles.model_type', 'App\\Models\\User')
        ->count();
}