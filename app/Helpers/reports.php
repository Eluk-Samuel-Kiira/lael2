<?php


use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Cache;

if (!function_exists('getOrderStatusColor')) {
    function getOrderStatusColor($status) {
        $colors = [
            'completed' => 'success',
            'processing' => 'info',
            'confirmed' => 'primary',
            'draft' => 'warning',
            'cancelled' => 'danger',
            'refunded' => 'secondary'
        ];
        
        return $colors[$status] ?? 'secondary';
    }
}

if (!function_exists('getOrderTypeColor')) {
    function getOrderTypeColor($type) {
        $colors = [
            'sale' => 'success',
            'return' => 'danger',
            'quote' => 'info',
            'layby' => 'warning'
        ];
        
        return $colors[$type] ?? 'secondary';
    }
}




if (!function_exists('getPaymentMethodsByType')) {
    function getPaymentMethodsByType($type = null) {
        $user = auth()->user();

        if (!$user) {
            return $type ? collect([]) : collect();
        }

        $tenantId = $user->tenant_id ?? null;

        if (!$tenantId) {
            return $type ? collect([]) : collect();
        }

        // ✅ All locations the user has access to (from location_user pivot)
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // ✅ The location the user is currently operating in, if any
        $activeLocationId = session('current_location_id')
            ?? (request()->filled('location') ? (int) request('location') : null);

        // Ignore it if the user doesn't actually have access
        if ($activeLocationId && !in_array((int) $activeLocationId, $userLocationIds, true)) {
            $activeLocationId = null;
        }

        // ✅ Which location IDs to filter by
        $filterLocationIds = $activeLocationId
            ? [(int) $activeLocationId]
            : $userLocationIds;

        // ✅ Cache key reflects both the tenant AND the locations used
        $cacheKey = "tenant_{$tenantId}_payment_methods_grouped";
        if (!empty($filterLocationIds)) {
            sort($filterLocationIds);
            $cacheKey .= '_locations_' . implode('_', $filterLocationIds);
        }

        $methods = Cache::remember($cacheKey, 3600, function () use ($tenantId, $filterLocationIds) {
            $query = PaymentMethod::where('tenant_id', $tenantId)
                ->where('is_active', true);

            if (empty($filterLocationIds)) {
                // User has no locations → only globally-available methods
                $query->whereNull('location_id');
            } else {
                $query->where(function ($q) use ($filterLocationIds) {
                    // Globally available
                    $q->whereNull('location_id');

                    // OR matches any of the user's locations
                    foreach ($filterLocationIds as $locId) {
                        $q->orWhereRaw(
                            'JSON_CONTAINS(location_id, ?)',
                            [json_encode((string) $locId)]
                        );
                    }
                });
            }

            return $query->get()->groupBy('type');
        });

        if ($type) {
            return $methods[$type] ?? collect([]);
        }

        return $methods;
    }
}


if (!function_exists('getUniquePaymentTypes')) {
    function getUniquePaymentTypes() {
        $user = auth()->user();

        if (!$user) {
            return [];
        }

        $tenantId = $user->tenant_id ?? null;

        if (!$tenantId) {
            return [];
        }

        // ✅ All locations the user has access to (from location_user pivot)
        $userLocationIds = $user->locations()->pluck('locations.id')->toArray();

        // ✅ The location the user is currently operating in, if any
        $activeLocationId = session('current_location_id')
            ?? (request()->filled('location') ? (int) request('location') : null);

        // Ignore it if the user doesn't actually have access
        if ($activeLocationId && !in_array((int) $activeLocationId, $userLocationIds, true)) {
            $activeLocationId = null;
        }

        // ✅ Which location IDs to filter by
        $filterLocationIds = $activeLocationId
            ? [(int) $activeLocationId]
            : $userLocationIds;

        // ✅ Cache key reflects both the tenant AND the locations used
        $cacheKey = "tenant_{$tenantId}_payment_types";
        if (!empty($filterLocationIds)) {
            sort($filterLocationIds);
            $cacheKey .= '_locations_' . implode('_', $filterLocationIds);
        }

        return Cache::remember($cacheKey, 3600, function () use ($tenantId, $filterLocationIds) {
            $query = PaymentMethod::where('tenant_id', $tenantId)
                ->where('is_active', true);

            if (empty($filterLocationIds)) {
                // User has no locations → only globally-available methods
                $query->whereNull('location_id');
            } else {
                $query->where(function ($q) use ($filterLocationIds) {
                    // Globally available
                    $q->whereNull('location_id');

                    // OR matches any of the user's locations
                    foreach ($filterLocationIds as $locId) {
                        $q->orWhereRaw(
                            'JSON_CONTAINS(location_id, ?)',
                            [json_encode((string) $locId)]
                        );
                    }
                });
            }

            return $query->select('type')
                ->distinct()
                ->pluck('type')
                ->toArray();
        });
    }
}

if (!function_exists('getPaymentTypeIcon')) {
    function getPaymentTypeIcon($type) {
        $icons = [
            'cash' => 'ki-wallet',
            'card' => 'ki-credit-cart',
            'bank_account' => 'ki-bank',
            'mobile_money' => 'ki-phone',
            'digital_wallet' => 'ki-wallet',
            'check' => 'ki-document',
            'credit' => 'ki-time',
            'other' => 'ki-add-files'
        ];
        
        return $icons[$type] ?? 'ki-wallet';
    }
}

if (!function_exists('getPaymentTypeColor')) {
    function getPaymentTypeColor($type) {
        $colors = [
            'cash' => 'success',
            'card' => 'primary',
            'bank_account' => 'info',
            'mobile_money' => 'warning',
            'digital_wallet' => 'danger',
            'check' => 'dark',
            'credit' => 'secondary',
            'other' => 'secondary'
        ];
        
        return $colors[$type] ?? 'primary';
    }
}

if (!function_exists('getPaymentTypeLabel')) {
    function getPaymentTypeLabel($type) {
        return ucfirst(str_replace('_', ' ', $type));
    }
}