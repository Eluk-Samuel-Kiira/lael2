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








if (!function_exists('tenant_can')) {
    /**
     * Check if tenant has a specific module enabled
     * Works for both retail and hotel modules
     */
    function tenant_can(string $module): bool
    {
        $tenantId = auth()->user()->tenant_id;
        $settings = TenantSetting::getAllSettingsForTenant($tenantId);
        
        $moduleKey = 'module_' . $module;
        return isset($settings[$moduleKey]) && $settings[$moduleKey] == '1';
    }
}

if (!function_exists('tenant_is_hotel')) {
    /**
     * Check if tenant has hotel modules enabled
     */
    function tenant_is_hotel(): bool
    {
        return tenant_can('hotel_management') || 
               tenant_can('front_desk') || 
               tenant_can('room_management');
    }
}

if (!function_exists('tenant_limit')) {
    /**
     * Get tenant limit value
     */
    function tenant_limit(string $key, $default = 0)
    {
        $tenantId = auth()->user()->tenant_id;
        $settings = TenantSetting::getAllSettingsForTenant($tenantId);
        
        return $settings[$key] ?? $default;
    }
}

if (!function_exists('check_tenant_limit')) {
    /**
     * Check if tenant has reached a limit
     */
    function check_tenant_limit(string $resource, int $currentCount): bool
    {
        $limit = tenant_limit('max_' . $resource, 0);
        
        if ($limit >= 999999) { // Unlimited
            return true;
        }
        
        return $currentCount < $limit;
    }
}

if (!function_exists('get_tenant_plan')) {
    /**
     * Get current tenant plan
     */
    function get_tenant_plan()
    {
        $tenantId = auth()->user()->tenant_id;
        $settings = TenantSetting::getAllSettingsForTenant($tenantId);
        
        return $settings['billing_plan'] ?? 'free';
    }
}


// Example: Check before creating a new product
// public function store(Request $request)
// {
//     $tenantId = auth()->user()->tenant_id;
//     $productCount = Product::where('tenant_id', $tenantId)->count();
    
//     if (!check_tenant_limit('products', $productCount)) {
//         return response()->json([
//             'success' => false,
//             'message' => 'You have reached your product limit. Please upgrade your plan.'
//         ], 403);
//     }
    
//     // Proceed with product creation
// }

// // Example: Check if module is enabled
// public function index()
// {
//     if (!tenant_can('advanced_reports')) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Advanced reports are not included in your current plan.'
//         ], 403);
//     }
    
//     // Show advanced reports
// }