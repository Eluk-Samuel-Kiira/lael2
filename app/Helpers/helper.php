<?php


use App\Models\Setting;
use App\Models\Currency;
// protected array $cache = [];
use App\Models\{ TenantSetting, PaymentMethod };
use Illuminate\Support\Facades\Auth;
use App\Models\Location;

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




















/**
 * CLEAN CURRENCY HELPERS - Working Version
 * 
 * These helpers assume:
 * - Base currency is where is_base_currency = true
 * - Exchange rates are against base currency
 * - Amounts are stored as integers in base currency's smallest unit
 */

if (! function_exists('current_tenant_id')) {
    function current_tenant_id()
    {
        if (app()->runningInConsole()) {
            return \App\Models\Tenant::first()?->id;
        }
        
        return auth()->check() ? auth()->user()->tenant_id : null;
    }
}

if (! function_exists('current_location_id')) {
    function current_location_id()
    {
        if (app()->runningInConsole()) {
            return null;
        }
        
        return auth()->check() ? auth()->user()->location_id : null;
    }
}

if (!function_exists('get_tenant_base_currency')) {
    /**
     * Get base currency for tenant (where is_base_currency = true)
     */
    function get_tenant_base_currency($tenantId = null, $useCache = true): ?\App\Models\Currency
    {
        $tenantId = $tenantId ?? current_tenant_id();

        if (!$tenantId) {
            return null;
        }

        $cacheKey = "base_currency_tenant_{$tenantId}";

        if ($useCache && !app()->runningInConsole()) {
            return cache()->remember(
                $cacheKey,
                now()->addHours(24),
                function () use ($tenantId) {
                    return \App\Models\Currency::where('tenant_id', $tenantId)
                        ->where('is_base_currency', true)
                        ->where('is_active', true)
                        ->first();
                }
            );
        }

        return \App\Models\Currency::where('tenant_id', $tenantId)
            ->where('is_base_currency', true)
            ->where('is_active', true)
            ->first();
    }
}

if (!function_exists('get_location_currency')) {
    /**
     * Get current user's location currency
     * 
     * Your structure: Location has currency() relationship with currency_id FK
     * This correctly uses the relationship!
     */
    function get_location_currency($locationId = null): ?\App\Models\Currency
    {
        // For console/seeders, return base currency
        if (app()->runningInConsole()) {
            return get_tenant_base_currency();
        }

        // If location ID is provided, get that location's currency
        if ($locationId) {
            $location = \App\Models\Location::with('currency')->find($locationId);
            if ($location && $location->currency && $location->currency->is_active) {
                return $location->currency;
            }
        }

        // Check authenticated user
        if (auth()->check()) {
            $user = auth()->user();
            $tenantId = $user->tenant_id;

            // If user has location_id, load the location with currency
            if ($user->location_id) {
                // Eager load the currency relationship
                $location = \App\Models\Location::with('currency')
                    ->where('id', $user->location_id)
                    ->first();
                
                if ($location && $location->currency && $location->currency->is_active) {
                    return $location->currency;
                }
            }

            // Fallback to tenant's base currency
            return get_tenant_base_currency($tenantId);
        }

        // No user, no location ID - return base currency
        return get_tenant_base_currency();
    }
}


if (!function_exists('to_base_currency')) {
    /**
     * Convert from user's currency to base currency (stored as integer in smallest unit)
     */
    function to_base_currency($amount, $fromCurrency = null): ?int
    {
        if ($amount === null || $amount === '' || $amount == 0) {
            return 0;
        }

        // Get the currency object
        if (!$fromCurrency) {
            $fromCurrency = get_location_currency();
        } elseif (is_string($fromCurrency)) {
            $fromCurrency = \App\Models\Currency::where('code', $fromCurrency)
                ->where('tenant_id', current_tenant_id())
                ->where('is_active', true)
                ->first();
        }

        // Get base currency
        $baseCurrency = get_tenant_base_currency();

        if (!$baseCurrency) {
            return (int) round((float) $amount * 100);
        }

        if (!$fromCurrency) {
            $fromCurrency = $baseCurrency;
        }

        // If same currency, just convert to smallest unit
        if ($fromCurrency->id === $baseCurrency->id) {
            return (int) round((float) $amount * $fromCurrency->getMultiplier());
        }

        // SIMPLE FLOAT CALCULATION - This matches your direct calculation
        $amountInMainUnit = (float) $amount;
        $exchangeRate = (float) $fromCurrency->exchange_rate;
        
        // Convert to base currency and then to smallest unit
        $baseMainUnit = $amountInMainUnit / $exchangeRate;
        $smallestUnit = (int) round($baseMainUnit * $baseCurrency->getMultiplier());
        
        return $smallestUnit;
    }
}

if (!function_exists('from_base_currency')) {
    /**
     * Convert from base currency (stored integer) to user's currency (float for display)
     */
    function from_base_currency(?int $storedAmount, $toCurrency = null): ?float
    {
        if ($storedAmount === null || $storedAmount == 0) {
            return 0.0;
        }

        $baseCurrency = get_tenant_base_currency();

        if (!$baseCurrency) {
            return (float) ($storedAmount / 100);
        }

        // Get target currency
        if (!$toCurrency) {
            $toCurrency = get_location_currency();
        } elseif (is_string($toCurrency)) {
            $toCurrency = \App\Models\Currency::where('code', $toCurrency)
                ->where('tenant_id', current_tenant_id())
                ->where('is_active', true)
                ->first();
        }

        if (!$toCurrency) {
            $toCurrency = $baseCurrency;
        }

        // If same currency, just convert from smallest unit
        if ($toCurrency->id === $baseCurrency->id) {
            return (float) ($storedAmount / $baseCurrency->getMultiplier());
        }

        // FIXED: Simple float calculation - matches your manual step-by-step
        $baseMainUnit = $storedAmount / $baseCurrency->getMultiplier(); // Convert cents to dollars (263 / 100 = 2.63)
        $targetMainUnit = $baseMainUnit * (float) $toCurrency->exchange_rate; // Multiply by exchange rate (2.63 * 1.52 = 3.9976)
        
        // Round to target currency's decimal places
        return round($targetMainUnit, $toCurrency->decimal_places); // Round to 2 decimals = 4.00
    }
}

if (!function_exists('bcround')) {
    /**
     * Round a BCMath string number to specified decimal places
     */
    function bcround(string $number, int $precision = 0): string
    {
        if (strpos($number, '.') === false) {
            return $number;
        }

        $parts = explode('.', $number);
        $wholePart = $parts[0];
        $decimalPart = str_pad($parts[1] ?? '0', $precision + 1, '0');

        $digitToRound = (int) ($decimalPart[$precision] ?? '0');
        $roundedDecimal = substr($decimalPart, 0, $precision);

        if ($digitToRound >= 5) {
            if ($precision === 0) {
                $wholePart = (string) ((int) $wholePart + 1);
            } else {
                $roundedDecimal = (string) ((int) $roundedDecimal + 1);
                $roundedDecimal = str_pad($roundedDecimal, $precision, '0', STR_PAD_LEFT);
            }
        }

        if ($precision === 0) {
            return $wholePart;
        }

        return $wholePart . '.' . str_pad($roundedDecimal, $precision, '0', STR_PAD_RIGHT);
    }
}

if (!function_exists('format_currency')) {
    /**
     * Format amount with currency symbol and formatting
     */
    function format_currency(?float $amount, $currency = null): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        // Get currency object
        if (!$currency) {
            $currency = get_location_currency();
        } elseif (is_string($currency)) {
            $currency = \App\Models\Currency::where('code', $currency)
                ->where('tenant_id', current_tenant_id())
                ->where('is_active', true)
                ->first();
        } elseif (is_numeric($currency)) {
            // If currency ID is passed
            $currency = \App\Models\Currency::find($currency);
        }

        if (!$currency) {
            return number_format((float) $amount, 2);
        }

        return $currency->format((float) $amount);
    }
}

if (!function_exists('convert_currency')) {
    /**
     * Convert amount between any two currencies
     */
    function convert_currency(int $smallestUnitAmount, $fromCurrency, $toCurrency): float
    {
        // Get currency objects if IDs or codes are passed
        if (is_numeric($fromCurrency)) {
            $fromCurrency = \App\Models\Currency::find($fromCurrency);
        } elseif (is_string($fromCurrency)) {
            $fromCurrency = \App\Models\Currency::where('code', $fromCurrency)->where('is_active', true)->first();
        }

        if (is_numeric($toCurrency)) {
            $toCurrency = \App\Models\Currency::find($toCurrency);
        } elseif (is_string($toCurrency)) {
            $toCurrency = \App\Models\Currency::where('code', $toCurrency)->where('is_active', true)->first();
        }

        if (!$fromCurrency || !$toCurrency) {
            return 0.0;
        }

        // If same currency, just convert from smallest unit
        if ($fromCurrency->id === $toCurrency->id) {
            return (float) bcdiv((string) $smallestUnitAmount, (string) $fromCurrency->getMultiplier(), $fromCurrency->decimal_places);
        }

        // Get base currency for this tenant
        $baseCurrency = get_tenant_base_currency($fromCurrency->tenant_id ?? current_tenant_id());

        if (!$baseCurrency) {
            return 0.0;
        }

        // Step 1: Convert from source currency to base
        $fromMultiplier = (string) $fromCurrency->getMultiplier();
        $baseMultiplier = (string) $baseCurrency->getMultiplier();
        $fromRate = (string) $fromCurrency->exchange_rate;
        $baseRate = (string) $baseCurrency->exchange_rate;

        // Convert from source smallest unit to source decimal
        $sourceDecimal = bcdiv((string) $smallestUnitAmount, $fromMultiplier, 10);

        // Convert from source to base
        $baseDecimal = bcdiv($sourceDecimal, $fromRate, 10);
        $baseDecimal = bcmul($baseDecimal, $baseRate, 10);

        // Step 2: Convert from base to target
        $toRate = (string) $toCurrency->exchange_rate;
        $targetDecimal = bcmul($baseDecimal, $toRate, 10);

        return (float) bcround($targetDecimal, $toCurrency->decimal_places);
    }
}

if (! function_exists('currency_symbol')) {
    function currency_symbol($locationId = null): string
    {
        $currency = get_location_currency($locationId);
        return $currency->symbol ?? '$';
    }
}

if (! function_exists('currency_code')) {
    function currency_code($locationId = null): string
    {
        $currency = get_location_currency($locationId);
        return $currency->code ?? 'USD';
    }
}

if (!function_exists('clear_currency_cache')) {
    /**
     * Clear currency caches when data is updated
     */
    function clear_currency_cache($tenantId = null): void
    {
        $tenantId = $tenantId ?? current_tenant_id();

        if (!$tenantId) {
            return;
        }

        cache()->forget("base_currency_tenant_{$tenantId}");
    }
}


if (!function_exists('user_location_currency')) {
    /**
     * Get currency for user's location
     */
    function user_location_currency($userId = null): ?\App\Models\Currency
    {
        if ($userId) {
            $user = \App\Models\User::with('location.currency')->find($userId);
            return $user?->location?->currency;
        }

        if (!auth()->check()) {
            return null;
        }

        return get_location_currency();
    }
}

// Debug function to test
if (! function_exists('debug_currency_conversion')) {
    function debug_currency_conversion($amount = 7500, $fromCode = 'UGX')
    {
        $fromCurrency = \App\Models\Currency::where('code', $fromCode)->first();
        $baseCurrency = get_tenant_base_currency();
        
        if (!$fromCurrency || !$baseCurrency) {
            return "Missing currencies";
        }
        
        echo "=== CURRENCY CONVERSION DEBUG ===\n";
        echo "Amount: $amount {$fromCurrency->code}\n";
        echo "From Currency: {$fromCurrency->code} (decimals: {$fromCurrency->decimal_places}, multiplier: {$fromCurrency->getMultiplier()}, rate: {$fromCurrency->exchange_rate})\n";
        echo "Base Currency: {$baseCurrency->code} (decimals: {$baseCurrency->decimal_places}, multiplier: {$baseCurrency->getMultiplier()})\n\n";
        
        // Manual calculation
        $manualBaseMain = $amount / $fromCurrency->exchange_rate;
        $manualStored = round($manualBaseMain * $baseCurrency->getMultiplier());
        
        echo "MANUAL CALCULATION:\n";
        echo "  $amount ÷ {$fromCurrency->exchange_rate} = {$manualBaseMain} {$baseCurrency->code}\n";
        echo "  {$manualBaseMain} × {$baseCurrency->getMultiplier()} = {$manualStored} (stored)\n\n";
        
        // Using helper
        $stored = to_base_currency($amount, $fromCurrency);
        $back = from_base_currency($stored, $fromCurrency);
        
        echo "USING HELPERS:\n";
        echo "  to_base_currency({$amount}) = {$stored}\n";
        echo "  from_base_currency({$stored}) = {$back}\n\n";
        
        echo "RESULT: {$amount} {$fromCurrency->code} → {$back} {$fromCurrency->code}\n";
        echo "DIFFERENCE: " . ($amount - $back) . " {$fromCurrency->code}\n";
        
        return [
            'original' => $amount,
            'stored' => $stored,
            'returned' => $back,
            'difference' => $amount - $back
        ];
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
    /**
     * Get current authenticated tenant ID
     */
    function current_tenant_id(): ?int
    {
        return auth()->check() ? auth()->user()->tenant_id : null;
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