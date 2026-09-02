<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\InventoryItems;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Location;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LowStockAlertService
{
    protected $messagingService;

    public function __construct(MessagingService $messagingService = null)
    {
        $this->messagingService = $messagingService;
    }

    /**
     * Check low stock for all tenants and send alerts
     */
    public function checkAndAlert($tenantId = null)
    {
        $tenants = $tenantId 
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::all();

        $results = [];

        foreach ($tenants as $tenant) {
            $isSingleShop = tenant_is_single_shop($tenant->id);
            
            if ($isSingleShop) {
                $results[$tenant->id] = $this->checkSingleShopStock($tenant);
            } else {
                $results[$tenant->id] = $this->checkMultiShopStock($tenant);
            }
        }

        return $results;
    }

    /**
     * Check low stock for single shop tenants
     * Uses ProductVariant: overal_quantity_at_hand vs low_stock_level
     */
    protected function checkSingleShopStock($tenant)
    {
        // Find variants where quantity at hand is at or below low stock level
        $lowStockVariants = ProductVariant::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereColumn('overal_quantity_at_hand', '<=', 'low_stock_level')
            ->with(['product'])
            ->get();

        if ($lowStockVariants->isEmpty()) {
            return [
                'tenant_id' => $tenant->id,
                'status' => 'no_low_stock',
                'message' => 'No low stock items found',
                'items' => []
            ];
        }

        // Group by location (if product has location info)
        $grouped = $lowStockVariants->groupBy(function($variant) {
            return $variant->product->location_id ?? 'unassigned';
        });

        // Get admin users for this tenant
        $admins = User::where('tenant_id', $tenant->id)
            ->whereHas('roles', function($q) {
                $q->whereIn('name', ['admin', 'super_admin', 'manager']);
            })
            ->where('status', 'active')
            ->get();

        // Send alerts to each admin
        foreach ($admins as $admin) {
            $this->sendSingleShopAlert($admin, $lowStockVariants, $tenant);
        }

        return [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'status' => 'alerts_sent',
            'items_count' => $lowStockVariants->count(),
            'items' => $lowStockVariants->map(function($variant) {
                return [
                    'name' => $variant->name,
                    'sku' => $variant->sku,
                    'quantity' => $variant->overal_quantity_at_hand,
                    'low_stock_level' => $variant->low_stock_level,
                    'shortage' => $variant->low_stock_level - $variant->overal_quantity_at_hand,
                ];
            })->toArray()
        ];
    }

    /**
     * Check low stock for multi-shop tenants
     * Uses InventoryItems: quantity_on_hand vs preferred_stock_level
     */
    protected function checkMultiShopStock($tenant)
    {
        // Find inventory items where quantity is at or below preferred stock level
        $lowStockItems = InventoryItems::where('tenant_id', $tenant->id)
            ->whereColumn('quantity_on_hand', '<=', 'preferred_stock_level')
            ->with(['variant', 'itemLocation'])  // ✅ Eager load relationships
            ->get();

        if ($lowStockItems->isEmpty()) {
            return [
                'tenant_id' => $tenant->id,
                'status' => 'no_low_stock',
                'message' => 'No low stock items found',
                'items' => []
            ];
        }

        // Group by location
        $grouped = $lowStockItems->groupBy('location_id');

        // Get admin users for this tenant
        $admins = User::where('tenant_id', $tenant->id)
            ->whereHas('roles', function($q) {
                $q->whereIn('name', ['admin', 'super_admin', 'manager']);
            })
            ->where('status', 'active')
            ->get();

        // Send alerts to each admin
        foreach ($admins as $admin) {
            $this->sendMultiShopAlert($admin, $lowStockItems, $tenant, $grouped);
        }

        return [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'status' => 'alerts_sent',
            'items_count' => $lowStockItems->count(),
            'items' => $lowStockItems->map(function($item) {
                $variant = $item->variant;
                return [
                    'name' => $variant ? $variant->name : 'Unknown',
                    'sku' => $variant ? $variant->sku : 'N/A',
                    'location' => $item->itemLocation ? $item->itemLocation->name : 'Unassigned',
                    'quantity_on_hand' => $item->quantity_on_hand,
                    'preferred_stock_level' => $item->preferred_stock_level,
                    'shortage' => $item->preferred_stock_level - $item->quantity_on_hand,
                    'batch_number' => $item->batch_number ?? 'N/A',
                    'expiry_date' => $item->expiry_date,
                ];
            })->toArray()
        ];
    }

    /**
     * Send single shop low stock alert
     */
    protected function sendSingleShopAlert($admin, $lowStockVariants, $tenant)
    {
        $subject = "⚠️ Low Stock Alert - {$tenant->name}";
        
        // Build email content
        $content = $this->buildSingleShopAlertContent($admin, $lowStockVariants, $tenant);
        
        // Send email
        try {
            Mail::raw($content, function($message) use ($admin, $subject) {
                $message->to($admin->email)
                        ->subject($subject);
            });
            Log::info("Low stock alert email sent to {$admin->email} for tenant {$tenant->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send low stock email to {$admin->email}: " . $e->getMessage());
        }

        // Send WhatsApp if messaging service is available
        if ($this->messagingService && $admin->telephone_number) {
            try {
                $whatsappMessage = $this->buildSingleShopWhatsAppMessage($admin, $lowStockVariants, $tenant);
                $phone = $this->formatPhoneNumber($admin->telephone_number);
                $this->messagingService->sendWhatsApp($phone, [
                    'body' => $whatsappMessage
                ]);
                Log::info("Low stock WhatsApp alert sent to {$admin->telephone_number} for tenant {$tenant->id}");
            } catch (\Exception $e) {
                Log::error("Failed to send low stock WhatsApp to {$admin->telephone_number}: " . $e->getMessage());
            }
        }
    }

    /**
     * Send multi-shop low stock alert
     */
    protected function sendMultiShopAlert($admin, $lowStockItems, $tenant, $grouped)
    {
        $subject = "⚠️ Low Stock Alert - {$tenant->name}";
        
        // Build email content
        $content = $this->buildMultiShopAlertContent($admin, $lowStockItems, $tenant, $grouped);
        
        // Send email
        try {
            Mail::raw($content, function($message) use ($admin, $subject) {
                $message->to($admin->email)
                        ->subject($subject);
            });
            Log::info("Low stock alert email sent to {$admin->email} for tenant {$tenant->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send low stock email to {$admin->email}: " . $e->getMessage());
        }

        // Send WhatsApp if messaging service is available
        if ($this->messagingService && $admin->telephone_number) {
            try {
                $whatsappMessage = $this->buildMultiShopWhatsAppMessage($admin, $lowStockItems, $tenant, $grouped);
                $phone = $this->formatPhoneNumber($admin->telephone_number);
                $this->messagingService->sendWhatsApp($phone, [
                    'body' => $whatsappMessage
                ]);
                Log::info("Low stock WhatsApp alert sent to {$admin->telephone_number} for tenant {$tenant->id}");
            } catch (\Exception $e) {
                Log::error("Failed to send low stock WhatsApp to {$admin->telephone_number}: " . $e->getMessage());
            }
        }
    }

    /**
     * Build email content for single shop
     */
    protected function buildSingleShopAlertContent($admin, $lowStockVariants, $tenant)
    {
        $content = "⚠️ LOW STOCK ALERT\n\n";
        $content .= "Tenant: {$tenant->name}\n";
        $content .= "Alert Type: Single Shop\n";
        $content .= "Date: " . now()->setTimezone('Africa/Nairobi')->format('M d, Y H:i') . " EAT\n";
        $content .= "Recipient: {$admin->name}\n\n";
        $content .= "The following items are at or below their low stock levels:\n\n";
        $content .= str_repeat("=", 60) . "\n\n";

        foreach ($lowStockVariants as $index => $variant) {
            $shortage = $variant->low_stock_level - $variant->overal_quantity_at_hand;
            $content .= ($index + 1) . ". Product: {$variant->name}\n";
            $content .= "   SKU: {$variant->sku}\n";
            $content .= "   Current Quantity: {$variant->overal_quantity_at_hand}\n";
            $content .= "   Low Stock Level: {$variant->low_stock_level}\n";
            $content .= "   Shortage: " . ($shortage > 0 ? $shortage : 0) . " units\n";
            
            if ($variant->product) {
                $content .= "   Category: " . ($variant->product->category->name ?? 'N/A') . "\n";
            }
            
            $content .= "\n";
        }

        $content .= str_repeat("=", 60) . "\n\n";
        $content .= "📋 Action Required: Please review and reorder these items immediately.\n";
        $content .= "🔄 This alert will be sent every 3 days until stock is replenished.\n\n";
        $content .= "📅 Generated: " . now()->setTimezone('Africa/Nairobi')->format('M d, Y H:i') . " EAT\n";
        $content .= "🛒 System: Stock Management System\n";
        $content .= "🔔 This is an automated alert. Please do not reply to this email.";

        return $content;
    }

    /**
     * Build email content for multi-shop
     */
    protected function buildMultiShopAlertContent($admin, $lowStockItems, $tenant, $grouped)
    {
        $content = "⚠️ LOW STOCK ALERT\n\n";
        $content .= "Tenant: {$tenant->name}\n";
        $content .= "Alert Type: Multi-Shop\n";
        $content .= "Date: " . now()->setTimezone('Africa/Nairobi')->format('M d, Y H:i') . " EAT\n";
        $content .= "Recipient: {$admin->name}\n\n";
        $content .= "The following items are at or below their preferred stock levels:\n\n";
        $content .= str_repeat("=", 60) . "\n\n";

        foreach ($grouped as $locationId => $items) {
            $location = Location::find($locationId);
            $locationName = $location ? $location->name : 'Unassigned';
            
            $content .= "📍 LOCATION: {$locationName}\n";
            $content .= str_repeat("-", 40) . "\n";
            
            foreach ($items as $index => $item) {
                // FIX: Check if variant exists
                $variant = $item->variant;
                $variantName = $variant ? $variant->name : 'Unknown Product';
                $variantSku = $variant ? $variant->sku : 'N/A';
                
                $shortage = $item->preferred_stock_level - $item->quantity_on_hand;
                
                $content .= ($index + 1) . ". Product: {$variantName}\n";
                $content .= "   SKU: {$variantSku}\n";
                $content .= "   Quantity on Hand: {$item->quantity_on_hand}\n";
                $content .= "   Preferred Stock Level: {$item->preferred_stock_level}\n";
                $content .= "   Shortage: " . ($shortage > 0 ? $shortage : 0) . " units\n";
                $content .= "   Batch: " . ($item->batch_number ?? 'N/A') . "\n";
                $content .= "   Expiry: " . ($item->expiry_date ? date('M d, Y', strtotime($item->expiry_date)) : 'N/A') . "\n\n";
            }
            
            $content .= "\n";
        }

        $content .= str_repeat("=", 60) . "\n\n";
        $content .= "📋 Action Required: Please review and reorder these items immediately.\n";
        $content .= "🔄 This alert will be sent every 3 days until stock is replenished.\n\n";
        $content .= "📅 Generated: " . now()->setTimezone('Africa/Nairobi')->format('M d, Y H:i') . " EAT\n";
        $content .= "🛒 System: Stock Management System\n";
        $content .= "🔔 This is an automated alert. Please do not reply to this email.";

        return $content;
    }

    /**
     * Build WhatsApp message for single shop
     */
    protected function buildSingleShopWhatsAppMessage($admin, $lowStockVariants, $tenant)
    {
        $message = "⚠️ *LOW STOCK ALERT*\n\n";
        $message .= "🏢 *Tenant:* {$tenant->name}\n";
        $message .= "👤 *User:* {$admin->name}\n";
        $message .= "📅 *Date:* " . now()->setTimezone('Africa/Nairobi')->format('M d, Y H:i') . " EAT\n\n";
        $message .= "📋 *Items at low stock:*\n\n";
        $message .= "──────────────────\n\n";

        foreach ($lowStockVariants->take(5) as $variant) {
            $shortage = $variant->low_stock_level - $variant->overal_quantity_at_hand;
            $message .= "📦 *{$variant->name}*\n";
            $message .= "  SKU: {$variant->sku}\n";
            $message .= "  Quantity: {$variant->overal_quantity_at_hand}\n";
            $message .= "  Low Level: {$variant->low_stock_level}\n";
            $message .= "  Shortage: " . ($shortage > 0 ? $shortage : 0) . " units\n\n";
        }

        if ($lowStockVariants->count() > 5) {
            $remaining = $lowStockVariants->count() - 5;
            $message .= "➕ *And {$remaining} more items...*\n\n";
        }

        $message .= "──────────────────\n\n";
        $message .= "📋 *Action Required:* Review and reorder immediately.\n";
        $message .= "🔄 *Frequency:* This alert will be sent every 3 days.\n\n";
        $message .= "📅 Generated: " . now()->setTimezone('Africa/Nairobi')->format('M d, Y H:i') . " EAT";

        return $message;
    }

    /**
     * Build WhatsApp message for multi-shop
     */
    protected function buildMultiShopWhatsAppMessage($admin, $lowStockItems, $tenant, $grouped)
    {
        $message = "⚠️ *LOW STOCK ALERT*\n\n";
        $message .= "🏢 *Tenant:* {$tenant->name}\n";
        $message .= "👤 *User:* {$admin->name}\n";
        $message .= "📅 *Date:* " . now()->setTimezone('Africa/Nairobi')->format('M d, Y H:i') . " EAT\n\n";
        $message .= "📋 *Items at low stock:*\n\n";
        $message .= "──────────────────\n\n";

        $count = 0;
        foreach ($grouped as $locationId => $items) {
            if ($count >= 5) break;
            
            $location = Location::find($locationId);
            $locationName = $location ? $location->name : 'Unassigned';
            
            $message .= "📍 *{$locationName}*\n";
            
            foreach ($items as $item) {
                if ($count >= 5) break;
                
                // FIX: Check if variant exists before accessing properties
                $variant = $item->variant;
                $variantName = $variant ? $variant->name : 'Unknown Product';
                $variantSku = $variant ? $variant->sku : 'N/A';
                
                $shortage = $item->preferred_stock_level - $item->quantity_on_hand;
                
                $message .= "  📦 *{$variantName}*\n";
                $message .= "    SKU: {$variantSku}\n";
                $message .= "    Qty: {$item->quantity_on_hand} / Preferred: {$item->preferred_stock_level}\n";
                $message .= "    Shortage: " . ($shortage > 0 ? $shortage : 0) . " units\n";
                $message .= "    Batch: " . ($item->batch_number ?? 'N/A') . "\n";
                
                if ($item->expiry_date) {
                    $message .= "    Expiry: " . date('M d, Y', strtotime($item->expiry_date)) . "\n";
                }
                
                $message .= "\n";
                $count++;
            }
        }

        if ($lowStockItems->count() > 5) {
            $remaining = $lowStockItems->count() - 5;
            $message .= "➕ *And {$remaining} more items...*\n\n";
        }

        $message .= "──────────────────\n\n";
        $message .= "📋 *Action Required:* Review and reorder immediately.\n";
        $message .= "🔄 *Frequency:* This alert will be sent every 3 days.\n\n";
        $message .= "📅 Generated: " . now()->setTimezone('Africa/Nairobi')->format('M d, Y H:i') . " EAT";

        return $message;
    }

    /**
     * Format phone number for WhatsApp
     */
    private function formatPhoneNumber($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) === 9) {
            $phone = '254' . $phone;
        }
        
        if (strpos($phone, '0') === 0) {
            $phone = substr($phone, 1);
        }
        
        return '+' . $phone;
    }

    /**
     * Get low stock items for a specific tenant (API endpoint)
     */
    public function getLowStockItems($tenantId)
    {
        $isSingleShop = tenant_is_single_shop($tenantId);
        
        if ($isSingleShop) {
            return ProductVariant::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->whereColumn('overal_quantity_at_hand', '<=', 'low_stock_level')
                ->with(['product'])
                ->get();
        } else {
            return InventoryItems::where('tenant_id', $tenantId)
                ->whereColumn('quantity_on_hand', '<=', 'preferred_stock_level')
                ->with(['variant', 'itemLocation'])
                ->get();
        }
    }
}