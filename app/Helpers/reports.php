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
        $tenantId = auth()->user()->tenant_id ?? null;
        
        if (!$tenantId) {
            return $type ? collect([]) : [];
        }
        
        $methods = Cache::remember("tenant_{$tenantId}_payment_methods_grouped", 3600, function () use ($tenantId) {
            return PaymentMethod::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->get()
                ->groupBy('type');
        });
        
        if ($type) {
            return $methods[$type] ?? collect([]);
        }
        
        return $methods;
    }
}

if (!function_exists('getUniquePaymentTypes')) {
    function getUniquePaymentTypes() {
        $tenantId = auth()->user()->tenant_id ?? null;
        
        if (!$tenantId) {
            return [];
        }
        
        return Cache::remember("tenant_{$tenantId}_payment_types", 3600, function () use ($tenantId) {
            return PaymentMethod::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->select('type')
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