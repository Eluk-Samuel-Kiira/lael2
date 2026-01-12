<?php


use App\Models\Setting;
use App\Models\Currency;
// protected array $cache = [];
use App\Models\{ TenantSetting, PaymentMethod };
use Illuminate\Support\Facades\Auth;

if (! function_exists('is_tab_show')) {
    function is_tab_show($routeName)
    {
        return request()->routeIs($routeName) ? 'show' : '';
    }
}

if (! function_exists('is_route_active')) {
    function is_route_active($routeName)
    {
        return request()->routeIs($routeName) ? 'active' : '';
    }
}


if (!function_exists('trim_description')) {
    function trim_description($text, $wordLimit = 10)
    {
        $words = explode(' ', $text);
        if (count($words) > $wordLimit) {
            return implode(' ', array_slice($words, 0, $wordLimit)) . '...';
        }
        return $text;
    }
}

/**
 * Get default or first available payment method
 */
function getPaymentMethod(int $tenantId = null): ?PaymentMethod
{
    if (!$tenantId) {
        $tenantId = current_tenant_id() ?? Auth::user()->tenant_id ?? null;
    }
    
    if (!$tenantId) {
        return null;
    }
    
    return PaymentMethod::getDefaultOrFirstForTenant($tenantId);
}


if (!function_exists('getProfileImage')) {
    function getProfileImage()
    {
        $user = auth()->user();
        $defaultImage = asset('assets/media/avatars/300-3.jpg'); // Default image path

        // Check if the user exists and has a profile image
        if ($user && $user->profile_image) {
            // Use the stored relative path
            $filename = $user->profile_image;

            // Build the full path to the image using the public path
            $path = public_path('storage/' . $filename); // Full path to the file

            // Check if the file exists
            if (file_exists($path)) {
                // Return the URL to access the image
                return asset('storage/' . $filename); // Return the image URL
            }
        }

        // Return the default image URL if no profile image is found
        return $defaultImage;
    }
}

if (!function_exists('employeeProfileImage')) {
    function employeeProfileImage($profile_image)
    {
        $defaultImage = asset('assets/media/avatars/300-6.jpg'); // Default image path

        // Check if the user exists and has a profile image
        if ($profile_image) {
            // Use the stored relative path
            $filename = $profile_image;

            // Build the full path to the image using the public path
            $path = public_path('storage/' . $filename); // Full path to the file

            // Check if the file exists
            if (file_exists($path)) {
                // Return the URL to access the image
                return asset('storage/' . $filename); // Return the image URL
            }
        }

        // Return the default image URL if no profile image is found
        return $defaultImage;
    }
}




if (!function_exists('getLogoImage')) {
    function getLogoImage($tenantId = null)
    {
        // Get tenant ID from authenticated user if not provided
        $tenantId = $tenantId ?? (Auth::check() ? Auth::user()->tenant_id : null);
        
        // Try to get tenant-specific logo
        if ($tenantId) {
            $setting = Setting::where('tenant_id', $tenantId)->first();
            
            if ($setting && !empty($setting->logo)) {
                $path = 'logos/' . $setting->logo;
                
                // Check if file exists in storage/app/public/logos
                if (Storage::disk('public')->exists($path)) {
                    return asset('storage/' . $path);
                }
            }
        }
        
        // Fallback: try global settings (for super admin or public pages)
        $globalSetting = Setting::whereNull('tenant_id')->first();
        
        if ($globalSetting && !empty($globalSetting->logo)) {
            $path = 'logos/' . $globalSetting->logo;
            
            if (Storage::disk('public')->exists($path)) {
                return asset('storage/' . $path);
            }
        }
        
        // Final fallback: return default logo
        return asset('assets/media/logos/default-dark.svg');
    }
}


if (!function_exists('getFaviconImage')) {
    function getFaviconImage($tenantId = null, $forceRefresh = false)
    {
        // Get tenant ID from authenticated user if not provided
        $tenantId = $tenantId ?? (Auth::check() ? Auth::user()->tenant_id : null);
        
        // Create cache key
        $cacheKey = "favicon_{$tenantId}";
        
        // Return cached version if exists and not forcing refresh
        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        $faviconUrl = null;
        
        // Try to get tenant-specific favicon
        if ($tenantId) {
            $setting = Setting::where('tenant_id', $tenantId)->first();
            
            if ($setting && !empty($setting->favicon)) {
                $path = 'favicons/' . $setting->favicon;
                
                // Check if file exists in storage/app/public/favicons
                if (Storage::disk('public')->exists($path)) {
                    $faviconUrl = asset('storage/' . $path);
                }
            }
        }
        
        // If no tenant favicon found, try global settings
        if (!$faviconUrl) {
            $globalSetting = Setting::whereNull('tenant_id')->first();
            
            if ($globalSetting && !empty($globalSetting->favicon)) {
                $path = 'favicons/' . $globalSetting->favicon;
                
                if (Storage::disk('public')->exists($path)) {
                    $faviconUrl = asset('storage/' . $path);
                }
            }
        }
        
        // Final fallback: default favicon
        if (!$faviconUrl) {
            $faviconUrl = asset('assets/media/logos/favicon.png');
        }
        
        // Cache the result for 1 hour
        Cache::put($cacheKey, $faviconUrl, 3600);
        
        return $faviconUrl;
    }
}


// Auto currency converter
if (! function_exists('formatCurrency')) {
    function formatCurrency($amountUSD, $currencyCode = null)
    {
        // Get system default currency from settings if not passed
        $systemCurrency = $currencyCode ?? getMailOptions('currency');

        // Fetch currency from DB
        $currency = Currency::where('code', $systemCurrency)->first();

        if (! $currency) {
            // fallback to USD if not found
            $currency = Currency::where('isBaseCurrency', true)->first();
        }

        // Convert from USD → target currency
        $converted = $amountUSD * $currency->exchange_rate;

        // Return formatted number with symbol after value
        return $converted;
    }
}

if (! function_exists('toUSD')) {
    function toUSD($amount, $currencyCode = null)
    {
        // Clean the amount first
        $cleanAmount = cleanCurrencyValue($amount);
        
        // Get system default currency from settings if not passed
        $systemCurrency = $currencyCode ?? getMailOptions('currency');

        // Fetch currency from DB
        $currency = Currency::where('code', $systemCurrency)->first();

        if (! $currency) {
            $currency = Currency::where('isBaseCurrency', true)->first();
        }

        // Convert from target currency → USD
        return $cleanAmount / $currency->exchange_rate;
    }
}

if (! function_exists('cleanCurrencyValue')) {
    function cleanCurrencyValue($value)
    {
        if (is_string($value)) {
            // Remove currency symbols, commas, spaces
            return (float) preg_replace('/[^\d.]/', '', $value);
        }
        
        return (float) $value;
    }
}


// Pretty display
if (! function_exists('currencySymbol')) {
    function currencySymbol($currencyCode = null)
    {
        // Get system default currency from settings if not passed
        $systemCurrency = $currencyCode ?? getMailOptions('currency');

        // Fetch currency from DB
        $currency = Currency::where('code', $systemCurrency)->first();

        return $currency->symbol;
    }
}


// Pretty display
if (! function_exists('displayFormatedCurrency')) {
    function displayFormatedCurrency($amount)
    {
        // Return formatted number with symbol after value
        return number_format($amount, 2);
    }
}




if (!function_exists('getMailOptions')) {
    function getMailOptions($optionKey = null, $tenantId = null)
    {
        // Get tenant ID from authenticated user if not provided
        $tenantId = $tenantId ?? (Auth::check() ? Auth::user()->tenant_id : null);
        
        // Try to get tenant-specific mail settings
        if ($tenantId) {
            $setting = Setting::where('tenant_id', $tenantId)->first();
            
            if ($setting) {
                // If specific option requested
                if ($optionKey && isset($setting[$optionKey])) {
                    return $setting[$optionKey];
                }
                // If no specific option, return all mail-related settings
                return [
                    'mail_status' => $setting->mail_status ?? 'enabled',
                    'mail_mailer' => $setting->mail_mailer ?? 'smtp',
                    'mail_host' => $setting->mail_host ?? 'smtp.gmail.com',
                    'mail_port' => $setting->mail_port ?? '465',
                    'mail_username' => $setting->mail_username ?? '',
                    'mail_password' => $setting->mail_password ?? '',
                    'mail_encryption' => $setting->mail_encryption ?? 'tls',
                    'mail_address' => $setting->mail_address ?? '',
                    'mail_name' => $setting->mail_name ?? 'no_reply',
                ];
            }
        }
        
        // Fallback to global mail settings
        $globalSetting = Setting::whereNull('tenant_id')->first();
        
        if ($globalSetting) {
            if ($optionKey && isset($globalSetting[$optionKey])) {
                return $globalSetting[$optionKey];
            }
            
            return [
                'mail_status' => $globalSetting->mail_status ?? 'enabled',
                'mail_mailer' => $globalSetting->mail_mailer ?? 'smtp',
                'mail_host' => $globalSetting->mail_host ?? 'smtp.gmail.com',
                'mail_port' => $globalSetting->mail_port ?? '465',
                'mail_username' => $globalSetting->mail_username ?? '',
                'mail_password' => $globalSetting->mail_password ?? '',
                'mail_encryption' => $globalSetting->mail_encryption ?? 'tls',
                'mail_address' => $globalSetting->mail_address ?? '',
                'mail_name' => $globalSetting->mail_name ?? 'no_reply',
            ];
        }
        
        // Final fallback to env or defaults
        if ($optionKey) {
            $envKey = strtoupper($optionKey);
            return env($envKey, '');
        }
        
        return [
            'mail_status' => 'enabled',
            'mail_mailer' => env('MAIL_MAILER', 'smtp'),
            'mail_host' => env('MAIL_HOST', 'smtp.gmail.com'),
            'mail_port' => env('MAIL_PORT', '465'),
            'mail_username' => env('MAIL_USERNAME', ''),
            'mail_password' => env('MAIL_PASSWORD', ''),
            'mail_encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'mail_address' => env('MAIL_FROM_ADDRESS', ''),
            'mail_name' => env('MAIL_FROM_NAME', 'no_reply'),
        ];
    }
}


if (!function_exists('appDefaultName')) {
    function appDefaultName($tenantId = null)
    {
        // Get tenant ID from authenticated user if not provided
        $tenantId = $tenantId ?? (Auth::check() ? Auth::user()->tenant_id : null);
        
        // Try to get tenant-specific app name
        if ($tenantId) {
            $setting = Setting::where('tenant_id', $tenantId)->first();
            
            if ($setting && !empty($setting->app_name)) {
                // Extract the first word from the app_name
                $firstWord = explode(' ', trim($setting->app_name))[0];
                return $firstWord;
            }
        }
        
        // Fallback to global settings
        $globalSetting = Setting::whereNull('tenant_id')->first();
        
        if ($globalSetting && !empty($globalSetting->app_name)) {
            $firstWord = explode(' ', trim($globalSetting->app_name))[0];
            return $firstWord;
        }
        
        // Final fallback
        return 'Lael';
    }
}


if (!function_exists('productCategoryImage')) {
    function productCategoryImage($categoryImagePath = '')
    {
        $defaultImage = asset('assets/media/stock/ecommerce/123.png');

        if (!empty($categoryImagePath)) {
            $fullPath = public_path('storage/' . ltrim($categoryImagePath, '/'));
            if (file_exists($fullPath)) {
                return asset('storage/' . ltrim($categoryImagePath, '/'));
            }
        }

        return $defaultImage;
    }
}

if (!function_exists('productImage')) {
    function productImage($productImagePath = '')
    {
        $defaultImage = asset('assets/media/stock/ecommerce/123.png');

        if (!empty($productImagePath)) {
            $fullPath = public_path('storage/' . ltrim($productImagePath, '/'));
            if (file_exists($fullPath)) {
                return asset('storage/' . ltrim($productImagePath, '/'));
            }
        }

        return $defaultImage;
    }
}

if (!function_exists('productVariantImage')) {
    function productVariantImage($variantImagePath = '')
    {
        $defaultImage = asset('assets/media/stock/ecommerce/2.png');

        if (!empty($variantImagePath)) {
            $fullPath = public_path('storage/' . ltrim($variantImagePath, '/'));
            if (file_exists($fullPath)) {
                return asset('storage/' . ltrim($variantImagePath, '/'));
            }
        }

        return $defaultImage;
    }
}









if (!function_exists('current_tenant_id')) {
    function current_tenant_id()
    {
        return Auth::check() ? Auth::user()->tenant_id : null;
    }
}

if (!function_exists('current_tenant')) {
    function current_tenant()
    {
        if (!Auth::check()) {
            return null;
        }
    
        $tenantId = Auth::user()->tenant_id;
        return \App\Models\Tenant::find($tenantId);
    }
}


if (!function_exists('tenant_setting')) {
    /**
     * Get a tenant setting value directly from database
     */
    function tenant_setting($tenantId, string $key, $default = null)
    {
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
            'datetime' => $setting->setting_value ? \Carbon\Carbon::parse($setting->setting_value) : null,
            default => $setting->setting_value,
        };
    }
}

if (!function_exists('tenant_setting_set')) {
    /**
     * Set a tenant setting value directly to database
     */
    function tenant_setting_set($tenantId, string $key, $value, string $dataType = 'string', string $category = 'general'): void
    {
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
    }
}

if (!function_exists('tenant_limits')) {
    /**
     * Get all tenant limits directly from database
     */
    function tenant_limits($tenantId): array
    {
        return [
            'max_pos_shops' => tenant_setting($tenantId, 'max_pos_shops', 1),
            'max_locations' => tenant_setting($tenantId, 'max_locations', 1),
            'max_departments' => tenant_setting($tenantId, 'max_departments', 3),
            'max_users' => tenant_setting($tenantId, 'max_users', 3),
            'max_products' => tenant_setting($tenantId, 'max_products', 1000),
            'max_customers' => tenant_setting($tenantId, 'max_customers', 5000),
            'max_employees' => tenant_setting($tenantId, 'max_employees', 10),
            'max_terminals_per_shop' => tenant_setting($tenantId, 'max_terminals_per_shop', 2),
            'max_api_keys' => tenant_setting($tenantId, 'max_api_keys', 2),
            'max_webhooks' => tenant_setting($tenantId, 'max_webhooks', 5),
            'max_integrations' => tenant_setting($tenantId, 'max_integrations', 3),
            'data_retention_months' => tenant_setting($tenantId, 'data_retention_months', 24),
        ];
    }
}

if (!function_exists('tenant_features')) {
    /**
     * Get all tenant feature flags directly from database
     */
    function tenant_features($tenantId): array
    {
        return [
            'module_inventory' => tenant_setting($tenantId, 'module_inventory', true),
            'module_accounting' => tenant_setting($tenantId, 'module_accounting', true),
            'module_hr_payroll' => tenant_setting($tenantId, 'module_hr_payroll', false),
            'module_multicurrency' => tenant_setting($tenantId, 'module_multicurrency', false),
            'module_loyalty' => tenant_setting($tenantId, 'module_loyalty', false),
            'module_advanced_reports' => tenant_setting($tenantId, 'module_advanced_reports', false),
            'allow_data_export' => tenant_setting($tenantId, 'allow_data_export', false),
            'auto_backup_enabled' => tenant_setting($tenantId, 'auto_backup_enabled', true),
        ];
    }
}

if (!function_exists('tenant_module_enabled')) {
    /**
     * Check if a module is enabled for tenant directly from database
     */
    function tenant_module_enabled($tenantId, string $module): bool
    {
        return tenant_setting($tenantId, "module_$module", false);
    }
}

if (!function_exists('tenant_billing_info')) {
    /**
     * Get tenant billing information directly from database
     */
    function tenant_billing_info($tenantId): array
    {
        return [
            'plan' => tenant_setting($tenantId, 'billing_plan', 'starter'),
            'status' => tenant_setting($tenantId, 'subscription_status', 'active'),
            'trial_ends_at' => tenant_setting($tenantId, 'trial_ends_at'),
        ];
    }
}

if (!function_exists('tenant_is_single_shop')) {
    /**
     * Check if tenant is limited to single shop directly from database
     */
    function tenant_is_single_shop($tenantId): bool
    {
        $maxShops = tenant_setting($tenantId, 'max_pos_shops', 1);
        return $maxShops === 1;
    }
}

if (!function_exists('tenant_can_create_shops')) {
    /**
     * Check if tenant can create more shops directly from database
     */
    function tenant_can_create_shops($tenantId, int $currentShopCount): bool
    {
        $maxShops = tenant_setting($tenantId, 'max_pos_shops', 1);
        return $currentShopCount < $maxShops;
    }
}

if (!function_exists('tenant_remaining_shops')) {
    /**
     * Get remaining shops tenant can create directly from database
     */
    function tenant_remaining_shops($tenantId, int $currentShopCount): int
    {
        $maxShops = tenant_setting($tenantId, 'max_pos_shops', 1);
        return max(0, $maxShops - $currentShopCount);
    }
}

if (!function_exists('tenant_is_trial')) {
    /**
     * Check if tenant is in trial period directly from database
     */
    function tenant_is_trial($tenantId): bool
    {
        $billing = tenant_billing_info($tenantId);
        
        return $billing['status'] === 'trial' && 
               $billing['trial_ends_at'] && 
               now()->lt($billing['trial_ends_at']);
    }
}

if (!function_exists('tenant_plan')) {
    /**
     * Get tenant's current plan directly from database
     */
    function tenant_plan($tenantId): string
    {
        return tenant_setting($tenantId, 'billing_plan', 'starter');
    }
}

if (!function_exists('tenant_is_on_plan')) {
    /**
     * Check if tenant is on specific plan directly from database
     */
    function tenant_is_on_plan($tenantId, string $plan): bool
    {
        return tenant_plan($tenantId) === $plan;
    }
}

if (!function_exists('tenant_clear_settings_cache')) {
    /**
     * Clear tenant settings cache - now a no-op since we don't use cache
     */
    function tenant_clear_settings_cache($tenantId): void
    {
        // No cache to clear since we're reading directly from database
    }
}

if (!function_exists('tenant_get_all_settings')) {
    /**
     * Get all settings for a tenant directly from database
     */
    function tenant_get_all_settings($tenantId): array
    {
        return TenantSetting::where('tenant_id', $tenantId)
            ->get()
            ->mapWithKeys(function ($setting) {
                $value = match($setting->data_type) {
                    'integer' => (int) $setting->setting_value,
                    'boolean' => (bool) $setting->setting_value,
                    'json' => json_decode($setting->setting_value, true),
                    'datetime' => $setting->setting_value ? \Carbon\Carbon::parse($setting->setting_value) : null,
                    default => $setting->setting_value,
                };
                
                return [$setting->setting_key => $value];
            })
            ->toArray();
    }
}