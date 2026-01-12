<?php

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