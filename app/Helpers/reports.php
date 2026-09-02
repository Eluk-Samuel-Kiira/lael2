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
        $tenantId = $user->tenant_id ?? null;
        $userLocationId = $user->location_id ?? null;
        
        if (!$tenantId) {
            return $type ? collect([]) : [];
        }
        
        $cacheKey = "tenant_{$tenantId}_payment_methods_grouped";
        
        // Add location to cache key if user has a location
        if ($userLocationId) {
            $cacheKey .= "_location_{$userLocationId}";
        }
        
        $methods = Cache::remember($cacheKey, 3600, function () use ($tenantId, $userLocationId) {
            $query = PaymentMethod::where('tenant_id', $tenantId)
                ->where('is_active', true);
            
            // Filter by location if user has one
            if ($userLocationId) {
                $query->where(function($q) use ($userLocationId) {
                    $q->whereNull('location_id')
                      ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$userLocationId)]);
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
        $tenantId = $user->tenant_id ?? null;
        $userLocationId = $user->location_id ?? null;
        
        if (!$tenantId) {
            return [];
        }
        
        $cacheKey = "tenant_{$tenantId}_payment_types";
        
        // Add location to cache key if user has a location
        if ($userLocationId) {
            $cacheKey .= "_location_{$userLocationId}";
        }
        
        return Cache::remember($cacheKey, 3600, function () use ($tenantId, $userLocationId) {
            $query = PaymentMethod::where('tenant_id', $tenantId)
                ->where('is_active', true);
            
            // Filter by location if user has one
            if ($userLocationId) {
                $query->where(function($q) use ($userLocationId) {
                    $q->whereNull('location_id')
                      ->orWhereRaw('JSON_CONTAINS(location_id, ?)', [json_encode((string)$userLocationId)]);
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