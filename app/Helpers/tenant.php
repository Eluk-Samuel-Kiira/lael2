<?php

// Add this to your helpers.php file or create a new helper file

use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

if (!function_exists('current_tenant_id')) {
    /**
     * Get current tenant ID from various sources
     * 
     * @return int|null
     */
    function current_tenant_id(): ?int
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

if (!function_exists('current_tenant')) {
    /**
     * Get current tenant model
     * 
     * @return \App\Models\Tenant|null
     */
    function current_tenant()
    {
        $tenantId = current_tenant_id();
        
        if (!$tenantId) {
            return null;
        }
        
        return Tenant::find($tenantId);
    }
}

if (!function_exists('tenant_setting')) {
    /**
     * Get a tenant setting value
     * 
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @param string $key Setting key
     * @param mixed $default Default value if setting not found
     * @return mixed
     */
    function tenant_setting($tenantId = null, string $key, $default = null)
    {
        $tenantId = $tenantId ?? current_tenant_id();
        
        if (!$tenantId) {
            return $default;
        }

        $setting = TenantSetting::where('tenant_id', $tenantId)
            ->where('setting_key', $key)
            ->first();

        if (!$setting) {
            return $default;
        }

        // Cast value based on data_type
        return match($setting->data_type) {
            'integer' => (int) $setting->setting_value,
            'boolean' => (bool) $setting->setting_value,
            'json' => json_decode($setting->setting_value, true),
            'decimal' => (float) $setting->setting_value,
            'datetime' => $setting->setting_value ? \Carbon\Carbon::parse($setting->setting_value) : null,
            default => $setting->setting_value,
        };
    }
}

if (!function_exists('tenant_setting_set')) {
    /**
     * Set a tenant setting value
     * 
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @param string $dataType Data type (integer, boolean, string, json, decimal, datetime)
     * @param string $category Setting category
     * @return void
     */
    function tenant_setting_set($tenantId = null, string $key, $value, string $dataType = 'string', string $category = 'general'): void
    {
        $tenantId = $tenantId ?? current_tenant_id();
        
        if (!$tenantId) {
            return;
        }
        
        TenantSetting::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'setting_key' => $key,
            ],
            [
                'setting_value' => $value,
                'data_type' => $dataType,
                'category' => $category,
                'updated_at' => now(),
            ]
        );
        
        // Clear any cached settings
        tenant_clear_settings_cache($tenantId);
    }
}

if (!function_exists('tenant_settings_all')) {
    /**
     * Get all settings for a tenant
     * 
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @param bool $useCache Whether to use cache
     * @return array
     */
    function tenant_settings_all($tenantId = null, bool $useCache = true): array
    {
        $tenantId = $tenantId ?? current_tenant_id();
        
        if (!$tenantId) {
            return [];
        }
        
        if ($useCache) {
            return Cache::remember("tenant_settings.{$tenantId}", 3600, function () use ($tenantId) {
                return tenant_get_all_settings_from_db($tenantId);
            });
        }
        
        return tenant_get_all_settings_from_db($tenantId);
    }
}

if (!function_exists('tenant_get_all_settings_from_db')) {
    /**
     * Get all settings directly from database without cache
     * 
     * @param int $tenantId
     * @return array
     */
    function tenant_get_all_settings_from_db(int $tenantId): array
    {
        return TenantSetting::where('tenant_id', $tenantId)
            ->get()
            ->mapWithKeys(function ($setting) {
                $value = match($setting->data_type) {
                    'integer' => (int) $setting->setting_value,
                    'boolean' => (bool) $setting->setting_value,
                    'json' => json_decode($setting->setting_value, true),
                    'decimal' => (float) $setting->setting_value,
                    'datetime' => $setting->setting_value ? \Carbon\Carbon::parse($setting->setting_value) : null,
                    default => $setting->setting_value,
                };
                
                return [$setting->setting_key => $value];
            })
            ->toArray();
    }
}

if (!function_exists('tenant_clear_settings_cache')) {
    /**
     * Clear tenant settings cache
     * 
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @return void
     */
    function tenant_clear_settings_cache($tenantId = null): void
    {
        $tenantId = $tenantId ?? current_tenant_id();
        
        if ($tenantId) {
            Cache::forget("tenant_settings.{$tenantId}");
        }
    }
}

if (!function_exists('tenant_can')) {
    /**
     * Check if tenant has a specific module enabled
     * 
     * @param string $module Module name (inventory, accounting, hr_payroll, etc.)
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @return bool
     */
    function tenant_can(string $module, $tenantId = null): bool
    {
        $tenantId = $tenantId ?? current_tenant_id();
        
        if (!$tenantId) {
            return false;
        }
        
        $moduleKey = 'module_' . $module;
        $settings = tenant_settings_all($tenantId);
        
        return isset($settings[$moduleKey]) && $settings[$moduleKey] == true;
    }
}


if (!function_exists('tenant_includes')) {
    /**
     * Check if tenant has a specific module enabled
     * 
     * @param string $module Module name (inventory, accounting, hr_payroll, etc.)
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @return bool
     */
    function tenant_includes(string $module, $tenantId = null): bool
    {
        $tenantId = $tenantId ?? current_tenant_id();
        
        if (!$tenantId) {
            return false;
        }
        
        $moduleKey = 'includes_' . $module;
        $settings = tenant_settings_all($tenantId);
        
        return isset($settings[$moduleKey]) && $settings[$moduleKey] == true;
    }
}

if (!function_exists('tenant_limit')) {
    /**
     * Get tenant limit value
     * 
     * @param string $key Limit key (users, products, locations, etc.)
     * @param mixed $default Default value
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @return mixed
     */
    function tenant_limit(string $key, $default = 0, $tenantId = null)
    {
        $tenantId = $tenantId ?? current_tenant_id();
        
        if (!$tenantId) {
            return $default;
        }
        
        // Handle both with and without max_ prefix
        $settingKey = str_starts_with($key, 'max_') ? $key : 'max_' . $key;
        $settings = tenant_settings_all($tenantId);
        
        return $settings[$settingKey] ?? $default;
    }
}

if (!function_exists('tenant_check_limit')) {
    /**
     * Check if tenant has reached a limit
     * 
     * @param string $resource Resource name (users, products, locations, etc.)
     * @param int $currentCount Current count of resource
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @return bool True if under limit, False if reached/exceeded
     */
    function tenant_check_limit(string $resource, int $currentCount, $tenantId = null): bool
    {
        $limit = tenant_limit($resource, 0, $tenantId);
        
        if ($limit >= 999999) { // Unlimited
            return true;
        }
        
        return $currentCount < $limit;
    }
}

if (!function_exists('tenant_remaining')) {
    /**
     * Get remaining count for a resource before hitting limit
     * 
     * @param string $resource Resource name (users, products, locations, etc.)
     * @param int $currentCount Current count of resource
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @return int Remaining count
     */
    function tenant_remaining(string $resource, int $currentCount, $tenantId = null): int
    {
        $limit = tenant_limit($resource, 0, $tenantId);
        
        if ($limit >= 999999) { // Unlimited
            return PHP_INT_MAX;
        }
        
        return max(0, $limit - $currentCount);
    }
}

if (!function_exists('tenant_plan')) {
    /**
     * Get tenant's current plan
     * 
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @return string
     */
    function tenant_plan($tenantId = null): string
    {
        $tenantId = $tenantId ?? current_tenant_id();
        
        if (!$tenantId) {
            return 'unknown';
        }
        
        return tenant_setting($tenantId, 'billing_plan', 'free');
    }
}

if (!function_exists('tenant_is_on_plan')) {
    /**
     * Check if tenant is on specific plan
     * 
     * @param string $plan Plan code (free, starter, business, enterprise, onetime_lifetime)
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @return bool
     */
    function tenant_is_on_plan(string $plan, $tenantId = null): bool
    {
        return tenant_plan($tenantId) === $plan;
    }
}

if (!function_exists('tenant_is_lifetime')) {
    /**
     * Check if tenant has lifetime license
     * 
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @return bool
     */
    function tenant_is_lifetime($tenantId = null): bool
    {
        $tenantId = $tenantId ?? current_tenant_id();
        
        if (!$tenantId) {
            return false;
        }
        
        $settings = tenant_settings_all($tenantId);
        
        // Check if it's lifetime plan or has is_lifetime flag
        return (isset($settings['billing_plan']) && $settings['billing_plan'] === 'onetime_lifetime') ||
               (isset($settings['is_lifetime']) && $settings['is_lifetime'] == true);
    }
}

if (!function_exists('tenant_is_trial')) {
    /**
     * Check if tenant is in trial period
     * 
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @return bool
     */
    function tenant_is_trial($tenantId = null): bool
    {
        $tenantId = $tenantId ?? current_tenant_id();
        
        if (!$tenantId) {
            return false;
        }
        
        $settings = tenant_settings_all($tenantId);
        $status = $settings['subscription_status'] ?? 'active';
        $trialEndsAt = $settings['trial_ends_at'] ?? null;
        
        return $status === 'trial' && 
               $trialEndsAt && 
               now()->lt($trialEndsAt);
    }
}

if (!function_exists('tenant_billing_info')) {
    /**
     * Get tenant billing information
     * 
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @return array
     */
    function tenant_billing_info($tenantId = null): array
    {
        $tenantId = $tenantId ?? current_tenant_id();
        
        if (!$tenantId) {
            return [
                'plan' => 'unknown',
                'status' => 'unknown',
                'trial_ends_at' => null,
                'billing_cycle' => null,
                'currency' => 'USD',
            ];
        }
        
        $settings = tenant_settings_all($tenantId);
        
        return [
            'plan' => $settings['billing_plan'] ?? 'free',
            'plan_name' => $settings['plan_name'] ?? 'Free Trial',
            'status' => $settings['subscription_status'] ?? 'active',
            'trial_ends_at' => $settings['trial_ends_at'] ?? null,
            'billing_cycle' => $settings['billing_cycle'] ?? 'monthly',
            'currency' => $settings['plan_currency'] ?? 'USD',
            'monthly_price' => $settings['plan_monthly_price'] ?? 0,
            'annual_price' => $settings['plan_annual_price'] ?? 0,
            'onetime_fee' => $settings['onetime_fee'] ?? 0,
            'is_lifetime' => $settings['is_lifetime'] ?? false,
        ];
    }
}

if (!function_exists('tenant_is_single_shop')) {
    /**
     * Check if tenant is limited to single shop
     * 
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @return bool
     */
    function tenant_is_single_shop($tenantId = null): bool
    {
        $maxShops = tenant_limit('shops', 1, $tenantId);
        return $maxShops === 1;
    }
}

if (!function_exists('tenant_can_create_shops')) {
    /**
     * Check if tenant can create more shops
     * 
     * @param int $currentShopCount Current number of shops
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @return bool
     */
    function tenant_can_create_shops(int $currentShopCount, $tenantId = null): bool
    {
        return tenant_check_limit('shops', $currentShopCount, $tenantId);
    }
}

if (!function_exists('tenant_remaining_shops')) {
    /**
     * Get remaining shops tenant can create
     * 
     * @param int $currentShopCount Current number of shops
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @return int
     */
    function tenant_remaining_shops(int $currentShopCount, $tenantId = null): int
    {
        return tenant_remaining('shops', $currentShopCount, $tenantId);
    }
}

if (!function_exists('tenant_features')) {
    /**
     * Get all tenant feature flags
     * 
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @return array
     */
    function tenant_features($tenantId = null): array
    {
        $tenantId = $tenantId ?? current_tenant_id();
        
        if (!$tenantId) {
            return [];
        }
        
        $settings = tenant_settings_all($tenantId);
        $features = [];
        
        foreach ($settings as $key => $value) {
            if (str_starts_with($key, 'module_') || str_starts_with($key, 'includes_')) {
                $features[$key] = $value;
            }
        }
        
        return $features;
    }
}

if (!function_exists('tenant_limits')) {
    /**
     * Get all tenant limits
     * 
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @return array
     */
    function tenant_limits($tenantId = null): array
    {
        $tenantId = $tenantId ?? current_tenant_id();
        
        if (!$tenantId) {
            return [];
        }
        
        $settings = tenant_settings_all($tenantId);
        $limits = [];
        
        foreach ($settings as $key => $value) {
            if (str_starts_with($key, 'max_')) {
                $limits[$key] = $value;
            }
        }
        
        return $limits;
    }
}

if (!function_exists('tenant_currency')) {
    /**
     * Get tenant currency code
     * 
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @return string
     */
    function tenant_currency($tenantId = null): string
    {
        return tenant_setting($tenantId, 'plan_currency', 'USD');
    }
}

if (!function_exists('tenant_timezone')) {
    /**
     * Get tenant timezone
     * 
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @return string
     */
    function tenant_timezone($tenantId = null): string
    {
        return tenant_setting($tenantId, 'timezone', 'UTC');
    }
}

if (!function_exists('tenant_locale')) {
    /**
     * Get tenant locale
     * 
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @return string
     */
    function tenant_locale($tenantId = null): string
    {
        return tenant_setting($tenantId, 'locale', 'en');
    }
}

if (!function_exists('tenant_is_hotel')) {
    /**
     * Check if tenant has hotel modules enabled
     * 
     * @param int|string|null $tenantId Tenant ID (null uses current tenant)
     * @return bool
     */
    function tenant_is_hotel($tenantId = null): bool
    {
        return tenant_can('hotel_management', $tenantId) || 
               tenant_can('front_desk', $tenantId) || 
               tenant_can('room_management', $tenantId);
    }
}

// ==================== USAGE EXAMPLES ====================

/*
// Get a single setting
$maxUsers = tenant_setting(current_tenant_id(), 'max_users', 10);
$moduleEnabled = tenant_setting(null, 'module_advanced_reports', false);

// Set a setting
tenant_setting_set(null, 'maintenance_mode', true, 'boolean', 'system');

// Check module access
if (tenant_can('advanced_reports')) {
    // Show advanced reports
}

// Check limits
$userCount = User::where('tenant_id', current_tenant_id())->count();
if (tenant_check_limit('users', $userCount)) {
    // Can create new user
} else {
    // Limit reached
}

// Get remaining count
$remaining = tenant_remaining('users', $userCount);
echo "You can create {$remaining} more users";

// Get all settings
$allSettings = tenant_settings_all();

// Get specific groups
$limits = tenant_limits();
$features = tenant_features();
$billing = tenant_billing_info();

// Check plan
if (tenant_is_on_plan('enterprise')) {
    // Enterprise features
}

if (tenant_is_lifetime()) {
    // Lifetime license features
}
*/