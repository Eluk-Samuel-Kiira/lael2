<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\ProductVariant;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\InventoryAdjustments;
use App\Models\Department;
use App\Models\Location;
use App\Models\SingleShopInventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class InventoryReportsController extends Controller
{
    /**
     * Get current tenant ID
     */
    private function getTenantId()
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('inventory reports')) {
            abort(403, __('payments.not_authorized'));
        }
        return $tenantId;
    }

    
    
    /**
     * ✅ Reusable pagination method
     */
    private function paginateCollection($collection, $perPage = 15, $pageName = 'page')
    {
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);
        $currentPageItems = $collection->slice(($page - 1) * $perPage, $perPage)->values();
        
        return new LengthAwarePaginator(
            $currentPageItems,
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
                'query' => request()->except($pageName, 'per_page')
            ]
        );
    }
    
    /**
     * Inventory Summary Report with Pagination
     */
    public function summary(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        // Filter parameters
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        $variantId = $request->get('variant_id');
        $isActive = $request->get('is_active', 'all');
        $perPage = (int)$request->get('per_page', 15);
        
        // Query for inventory items
        $query = InventoryItems::with(['variant', 'departmentItem', 'itemLocation'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Apply filters
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        if ($variantId) {
            $query->where('variant_id', $variantId);
        }
        
        if ($isActive !== 'all') {
            $query->whereHas('variant', function($q) use ($isActive) {
                $q->where('is_active', $isActive);
            });
        }
        
        // ✅ Get ALL items for summary calculations (unpaginated)
        $allItems = $query->get();
        
        // Summary statistics from ALL items
        $summary = [
            'total_items' => $allItems->count(),
            'total_quantity' => $allItems->sum('quantity_on_hand'),
            'total_value' => $this->calculateInventoryValueFromCollection($allItems),
            'average_stock_level' => $allItems->avg('quantity_on_hand'),
            'items_below_reorder' => $allItems->filter(function($item) {
                return $item->quantity_on_hand < $item->reorder_point;
            })->count(),
            'out_of_stock' => $allItems->where('quantity_on_hand', 0)->count(),
        ];
        
        // ✅ Apply pagination to the collection
        $inventoryItems = $this->paginateCollection($allItems, $perPage, 'page');
        
        // Get variants for filter dropdown
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get();
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.summary', compact(
            'inventoryItems',
            'summary',
            'variants',
            'departments',
            'locations',
            'startDate',
            'endDate',
            'departmentId',
            'locationId',
            'variantId',
            'isActive',
            'perPage'
        ));
    }
    
    
    /**
     * Helper method to calculate inventory value from query builder
     */
    private function calculateInventoryValue($query)
    {
        return $query->with(['variant'])
            ->get()
            ->sum(function ($item) {
                return $item->quantity_on_hand * ($item->variant->cost_price ?? 0);
            });
    }

    /**
     * Helper method to calculate inventory value from collection
     */
    private function calculateInventoryValueFromCollection($items)
    {
        return $items->sum(function ($item) {
            $costPrice = $item->variant->cost_price ?? 0;
            return $item->quantity_on_hand * $costPrice;
        });
    }


    /**
     * Inventory Transactions Report with Debug
     */
    public function transactions(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $type = $request->get('type', 'all');
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        $perPage = (int)$request->get('per_page', 15);
        
        // ✅ Build query with relationships
        $query = InventoryTransactions::with([
            'InventoryItems.variant.product',
            'InventoryItems.departmentItem',
            'InventoryItems.itemLocation'
        ])
        ->where('tenant_id', $tenantId)
        ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        if ($type !== 'all') {
            $query->where('type', $type);
        }
        
        if ($departmentId) {
            $query->whereHas('InventoryItems', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }
        
        if ($locationId) {
            $query->whereHas('InventoryItems', function($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        }
        
        // ✅ DEBUG: Check first transaction to see what's happening
        $firstTransaction = $query->first();
        if ($firstTransaction) {
            \Log::info('Transaction Debug:', [
                'transaction_id' => $firstTransaction->id,
                'inventory_id' => $firstTransaction->inventory_id,
                'has_inventory_item' => $firstTransaction->InventoryItems ? 'Yes' : 'No',
                'inventory_item_exists' => $firstTransaction->InventoryItems ? $firstTransaction->InventoryItems->id : 'N/A',
                'variant_id_from_inventory' => $firstTransaction->InventoryItems ? $firstTransaction->InventoryItems->variant_id : 'N/A',
            ]);
        }
        
        // Get paginated transactions
        $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
        
        // Get ALL transactions for summary
        $allTransactions = clone $query;
        $allTransactions = $allTransactions->get();
        
        // Calculate type summary
        $typeSummary = $allTransactions->groupBy('type')->map(function ($items, $typeKey) {
            return (object)[
                'type' => $typeKey,
                'count' => $items->count(),
                'total_quantity' => $items->sum('quantity'),
            ];
        })->values();
        
        // Calculate summary statistics
        $totalTransactions = $allTransactions->count();
        $totalQuantity = abs($allTransactions->sum('quantity'));
        $positiveTransactions = $allTransactions->where('quantity', '>', 0)->sum('quantity');
        $negativeTransactions = abs($allTransactions->where('quantity', '<', 0)->sum('quantity'));
        $netChange = $positiveTransactions - $negativeTransactions;
        $daysInRange = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $avgDailyTransactions = $daysInRange > 0 ? $totalTransactions / $daysInRange : 0;
        
        // Daily trend
        $dailyTrend = $allTransactions->groupBy(function ($transaction) {
            return $transaction->created_at->format('Y-m-d');
        })->map(function ($items, $date) {
            return (object)[
                'date' => $date,
                'count' => $items->count(),
                'quantity' => $items->sum('quantity')
            ];
        })->sortKeys()->values();
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.transactions', compact(
            'transactions',
            'typeSummary',
            'dailyTrend',
            'departments',
            'locations',
            'startDate',
            'endDate',
            'type',
            'departmentId',
            'locationId',
            'perPage',
            'totalTransactions',
            'totalQuantity',
            'positiveTransactions',
            'negativeTransactions',
            'netChange',
            'avgDailyTransactions'
        ));
    }                
        

    /**
     * Inventory Turnover Report with Pure Eloquent
     */
    public function turnover(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subMonths(6)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $variantId = $request->get('variant_id');
        $perPage = (int)$request->get('per_page', 15);
        
        // Calculate days in period
        $daysInPeriod = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        
        // Get all variants first (with their products)
        $variantsQuery = ProductVariant::with(['product'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);
        
        if ($variantId) {
            $variantsQuery->where('id', $variantId);
        }
        
        $allVariants = $variantsQuery->get();
        
        // Get inventory logs for the date range
        $logs = SingleShopInventoryLog::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->get();
        
        // Group logs by variant_id
        $logsByVariant = $logs->groupBy('variant_id');
        
        // Calculate turnover data for each variant using pure collections
        $turnoverCollection = $allVariants->map(function ($variant) use ($logsByVariant, $daysInPeriod) {
            $variantLogs = $logsByVariant->get($variant->id, collect());
            
            if ($variantLogs->isEmpty()) {
                return null;
            }
            
            // Calculate total movement (sum of absolute quantity changes)
            $totalMovement = $variantLogs->sum(function ($log) {
                return abs($log->quantity_change);
            });
            
            // Calculate average stock level
            $avgStockLevel = $variantLogs->avg('quantity_before') ?: 1;
            
            // Calculate transaction count
            $transactionCount = $variantLogs->count();
            
            // Get first and last movement dates
            $firstMovement = $variantLogs->min('created_at');
            $lastMovement = $variantLogs->max('created_at');
            
            // Calculate turnover rate
            $turnoverRate = $avgStockLevel > 0 ? $totalMovement / $avgStockLevel : 0;
            
            // Calculate days inventory held
            $daysInventoryHeld = $turnoverRate > 0 ? $daysInPeriod / $turnoverRate : $daysInPeriod;
            
            // Determine movement category
            if ($turnoverRate >= 10) {
                $movementCategory = 'fast';
                $movementLabel = __('pagination.fast_moving');
                $movementColor = 'danger';
            } elseif ($turnoverRate >= 1) {
                $movementCategory = 'slow';
                $movementLabel = __('pagination.slow_moving');
                $movementColor = 'warning';
            } else {
                $movementCategory = 'non';
                $movementLabel = __('pagination.non_moving');
                $movementColor = 'dark';
            }
            
            return (object)[
                'variant' => $variant,
                'total_movement' => $totalMovement,
                'avg_stock_level' => $avgStockLevel,
                'transaction_count' => $transactionCount,
                'first_movement' => $firstMovement,
                'last_movement' => $lastMovement,
                'turnover_rate' => $turnoverRate,
                'days_inventory_held' => $daysInventoryHeld,
                'movement_category' => $movementCategory,
                'movement_label' => $movementLabel,
                'movement_color' => $movementColor,
            ];
        })->filter(); // Remove null entries (variants with no movement)
        
        // Sort by turnover rate (descending)
        $sortedTurnover = $turnoverCollection->sortByDesc('turnover_rate')->values();
        
        // ✅ Apply pagination using your helper method
        $turnoverData = $this->paginateCollection($sortedTurnover, $perPage, 'page');
        
        // Calculate summary statistics from ALL data (not paginated)
        $summary = [
            'avg_turnover_rate' => $sortedTurnover->avg('turnover_rate') ?? 0,
            'avg_days_held' => $sortedTurnover->avg('days_inventory_held') ?? 0,
            'total_movement' => $sortedTurnover->sum('total_movement'),
            'total_transactions' => $sortedTurnover->sum('transaction_count'),
            'fast_moving' => $sortedTurnover->where('turnover_rate', '>=', 10)->count(),
            'slow_moving' => $sortedTurnover->whereBetween('turnover_rate', [1, 9.99])->count(),
            'non_moving' => $sortedTurnover->where('turnover_rate', '<', 1)->count(),
            'total_items' => $sortedTurnover->count(),
        ];
        
        // Get variants for filter dropdown
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get();
        
        return view('reports.inventory.turnover', compact(
            'turnoverData',
            'sortedTurnover',  // Pass for charts and summary
            'summary',
            'variants',
            'startDate',
            'endDate',
            'variantId',
            'perPage',
            'daysInPeriod'
        ));
    }
        
    /**
     * Stock Aging Report with Pure Eloquent
     */
    public function stockAging(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        $category = $request->get('category');
        $perPage = (int)$request->get('per_page', 15);
        
        // Get all inventory items with batch and expiry info
        $allItems = InventoryItems::with([
            'variant.product',
            'departmentItem',
            'itemLocation'
        ])
        ->where('tenant_id', $tenantId)
        ->where('quantity_on_hand', '>', 0)
        ->whereNotNull('batch_number')
        ->whereNotNull('expiry_date')
        ->when($departmentId, function($q, $departmentId) {
            return $q->where('department_id', $departmentId);
        })
        ->when($locationId, function($q, $locationId) {
            return $q->where('location_id', $locationId);
        })
        ->get();
        
        // Calculate aging metrics for each item
        $agingCollection = collect();
        $agingCategories = [
            'expired' => 0,
            '1_week' => 0,
            '1_month' => 0,
            '3_months' => 0,
            '6_months' => 0,
            'over_6_months' => 0,
        ];
        
        $today = now();
        
        foreach ($allItems as $item) {
            $expiryDate = Carbon::parse($item->expiry_date);
            $daysToExpiry = $today->diffInDays($expiryDate, false);
            
            // Determine aging category
            if ($daysToExpiry < 0) {
                $categoryKey = 'expired';
                $statusColor = 'danger';
                $statusText = __('pagination.expired');
                $progressColor = 'danger';
                $urgency = 'immediate';
            } elseif ($daysToExpiry <= 7) {
                $categoryKey = '1_week';
                $statusColor = 'warning';
                $statusText = __('pagination.critical');
                $progressColor = 'warning';
                $urgency = 'critical';
            } elseif ($daysToExpiry <= 30) {
                $categoryKey = '1_month';
                $statusColor = 'warning';
                $statusText = __('pagination.warning');
                $progressColor = 'warning';
                $urgency = 'high';
            } elseif ($daysToExpiry <= 90) {
                $categoryKey = '3_months';
                $statusColor = 'info';
                $statusText = __('pagination.monitor');
                $progressColor = 'info';
                $urgency = 'medium';
            } elseif ($daysToExpiry <= 180) {
                $categoryKey = '6_months';
                $statusColor = 'success';
                $statusText = __('pagination.good');
                $progressColor = 'success';
                $urgency = 'low';
            } else {
                $categoryKey = 'over_6_months';
                $statusColor = 'primary';
                $statusText = __('pagination.excellent');
                $progressColor = 'primary';
                $urgency = 'none';
            }
            
            // Add to category totals
            $agingCategories[$categoryKey] += $item->quantity_on_hand;
            
            // Filter by category if specified
            if ($category && $category !== $categoryKey) {
                continue;
            }
            
            $inventoryValue = $item->quantity_on_hand * ($item->variant->cost_price ?? 0);
            $valueAtRisk = $daysToExpiry < 90 ? $inventoryValue : 0;
            
            $agingCollection->push((object)[
                'id' => $item->id,
                'variant' => $item->variant,
                'departmentItem' => $item->departmentItem,
                'itemLocation' => $item->itemLocation,
                'quantity_on_hand' => $item->quantity_on_hand,
                'batch_number' => $item->batch_number,
                'expiry_date' => $item->expiry_date,
                'days_to_expiry' => $daysToExpiry,
                'inventory_value' => $inventoryValue,
                'value_at_risk' => $valueAtRisk,
                'category_key' => $categoryKey,
                'status_color' => $statusColor,
                'status_text' => $statusText,
                'progress_color' => $progressColor,
                'urgency' => $urgency,
                'cost_price' => $item->variant->cost_price ?? 0,
                'sku' => $item->variant->sku ?? '-',
                'barcode' => $item->variant->barcode ?? '-',
                'variant_name' => $item->variant->name ?? '-',
                'product_name' => $item->variant->product->name ?? '',
                'image_url' => $item->variant->image_url ?? null,
                'department_name' => $item->departmentItem->name ?? '-',
                'location_name' => $item->itemLocation->name ?? '-',
            ]);
        }
        
        // Sort by days to expiry (most urgent first)
        $sortedAging = $agingCollection->sortBy('days_to_expiry')->values();
        
        // Calculate summary
        $summary = [
            'expired' => $agingCategories['expired'],
            '1_week' => $agingCategories['1_week'],
            '1_month' => $agingCategories['1_month'],
            '3_months' => $agingCategories['3_months'],
            '6_months' => $agingCategories['6_months'],
            'over_6_months' => $agingCategories['over_6_months'],
            'total_items' => $sortedAging->count(),
            'total_value_at_risk' => $sortedAging->sum('value_at_risk'),
            'total_inventory_value' => $sortedAging->sum('inventory_value'),
        ];
        
        // Apply pagination
        $agingItems = $this->paginateCollection($sortedAging, $perPage, 'page');
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.stock-aging', compact(
            'agingItems',
            'summary',
            'agingCategories',
            'departments',
            'locations',
            'departmentId',
            'locationId',
            'category',
            'perPage'
        ));
    }
    
    /**
     * Low Stock Alerts Report with Pure Eloquent
     */
    public function lowStockAlerts(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        $severity = $request->get('severity', 'all');
        $perPage = (int)$request->get('per_page', 15);
        
        // Get all inventory items with relationships
        $allItems = InventoryItems::with([
            'variant.product',
            'departmentItem',
            'itemLocation'
        ])
        ->where('tenant_id', $tenantId)
        ->where('quantity_on_hand', '>', 0)
        ->when($departmentId, function($q, $departmentId) {
            return $q->where('department_id', $departmentId);
        })
        ->when($locationId, function($q, $locationId) {
            return $q->where('location_id', $locationId);
        })
        ->get();
        
        // Calculate low stock metrics using collections
        $lowStockCollection = collect();
        
        foreach ($allItems as $item) {
            $currentStock = $item->quantity_on_hand;
            $reorderPoint = $item->reorder_point ?? 0;
            $preferredStock = $item->preferred_stock_level ?? 0;
            
            // Skip if no reorder point or preferred stock is set
            if ($reorderPoint == 0 && $preferredStock == 0) {
                continue;
            }
            
            $isLowStock = false;
            $severityLevel = 'normal';
            $severityColor = 'success';
            $severityIcon = 'ki-check-circle';
            
            // Check against reorder point (critical threshold)
            if ($reorderPoint > 0) {
                if ($currentStock <= $reorderPoint * 0.5) {
                    $isLowStock = true;
                    $severityLevel = 'critical';
                    $severityColor = 'danger';
                    $severityIcon = 'ki-shield-cross';
                } elseif ($currentStock <= $reorderPoint) {
                    $isLowStock = true;
                    $severityLevel = 'warning';
                    $severityColor = 'warning';
                    $severityIcon = 'ki-shield-tick';
                }
            }
            
            // Also check against preferred stock (warning threshold)
            if (!$isLowStock && $preferredStock > 0) {
                if ($currentStock <= $preferredStock * 0.5) {
                    $isLowStock = true;
                    $severityLevel = 'warning';
                    $severityColor = 'warning';
                    $severityIcon = 'ki-shield-tick';
                }
            }
            
            // Filter by severity if specified
            if ($severity !== 'all' && $severityLevel !== $severity) {
                continue;
            }
            
            if ($isLowStock) {
                $deficit = max(0, $reorderPoint - $currentStock);
                $deficitValue = $deficit * ($item->variant->cost_price ?? 0);
                $reorderPercentage = $reorderPoint > 0 ? ($currentStock / $reorderPoint) * 100 : 0;
                $preferredPercentage = $preferredStock > 0 ? ($currentStock / $preferredStock) * 100 : 0;
                
                // Determine urgency
                if ($severityLevel === 'critical') {
                    $urgencyText = __('pagination.immediate');
                    $urgencyColor = 'danger';
                } elseif ($severityLevel === 'warning') {
                    $urgencyText = __('pagination.soon');
                    $urgencyColor = 'warning';
                } else {
                    $urgencyText = __('pagination.later');
                    $urgencyColor = 'info';
                }
                
                $lowStockCollection->push((object)[
                    'id' => $item->id,
                    'variant' => $item->variant,
                    'departmentItem' => $item->departmentItem,
                    'itemLocation' => $item->itemLocation,
                    'quantity_on_hand' => $currentStock,
                    'reorder_point' => $reorderPoint,
                    'preferred_stock_level' => $preferredStock,
                    'deficit' => $deficit,
                    'deficit_value' => $deficitValue,
                    'reorder_percentage' => $reorderPercentage,
                    'preferred_percentage' => $preferredPercentage,
                    'severity' => $severityLevel,
                    'severity_color' => $severityColor,
                    'severity_icon' => $severityIcon,
                    'severity_text' => $severityLevel === 'critical' ? __('pagination.critical') : ($severityLevel === 'warning' ? __('pagination.warning') : __('pagination.normal')),
                    'urgency_text' => $urgencyText,
                    'urgency_color' => $urgencyColor,
                    'cost_price' => $item->variant->cost_price ?? 0,
                    'sku' => $item->variant->sku ?? '-',
                    'barcode' => $item->variant->barcode ?? '-',
                    'variant_name' => $item->variant->name ?? '-',
                    'product_name' => $item->variant->product->name ?? '',
                    'image_url' => $item->variant->image_url ?? null,
                    'department_name' => $item->departmentItem->name ?? '-',
                    'location_name' => $item->itemLocation->name ?? '-',
                ]);
            }
        }
        
        // Sort by severity (critical first, then warning)
        $sortedLowStock = $lowStockCollection->sortBy(function($item) {
            $order = ['critical' => 1, 'warning' => 2, 'normal' => 3];
            return $order[$item->severity] ?? 4;
        })->values();
        
        // Calculate summary
        $summary = [
            'critical' => $sortedLowStock->where('severity', 'critical')->count(),
            'warning' => $sortedLowStock->where('severity', 'warning')->count(),
            'total_items' => $sortedLowStock->count(),
            'total_value_at_risk' => $sortedLowStock->sum('deficit_value'),
        ];
        
        // Apply pagination
        $lowStockItems = $this->paginateCollection($sortedLowStock, $perPage, 'page');
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.low-stock-alerts', compact(
            'lowStockItems',
            'summary',
            'departments',
            'locations',
            'departmentId',
            'locationId',
            'severity',
            'perPage'
        ));
    }
        
    
    /**
     * Inventory Adjustments Report with Pure Eloquent
     */
    public function adjustments(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        $perPage = (int)$request->get('per_page', 15);
        
        // Build query with relationships
        $query = InventoryAdjustments::with([
            'inventoryItems.variant.product',
            'inventoryItems.departmentItem',
            'inventoryItems.itemLocation',
            'createdBy'
        ])
        ->where('tenant_id', $tenantId)
        ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        if ($departmentId) {
            $query->whereHas('inventoryItems', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }
        
        if ($locationId) {
            $query->whereHas('inventoryItems', function($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        }
        
        // Get ALL adjustments for summary calculations (unpaginated)
        $allAdjustments = $query->get();
        
        // Calculate summary statistics using collections (no DB::raw)
        $summary = [
            'total_adjustments' => $allAdjustments->count(),
            'total_quantity_changed' => $allAdjustments->sum(function($item) {
                return abs($item->quantity_after - $item->quantity_before);
            }),
            'net_change' => $allAdjustments->sum(function($item) {
                return $item->quantity_after - $item->quantity_before;
            }),
            'increase_count' => $allAdjustments->filter(function($item) {
                return $item->quantity_after > $item->quantity_before;
            })->count(),
            'decrease_count' => $allAdjustments->filter(function($item) {
                return $item->quantity_after < $item->quantity_before;
            })->count(),
        ];
        
        // Get paginated adjustments for display
        $paginatedQuery = InventoryAdjustments::with([
            'inventoryItems.variant.product',
            'inventoryItems.departmentItem',
            'inventoryItems.itemLocation',
            'createdBy'
        ])
        ->where('tenant_id', $tenantId)
        ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
        ->orderBy('created_at', 'desc');
        
        if ($departmentId) {
            $paginatedQuery->whereHas('inventoryItems', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }
        
        if ($locationId) {
            $paginatedQuery->whereHas('inventoryItems', function($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        }
        
        $adjustments = $paginatedQuery->paginate($perPage)->withQueryString();
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.adjustments', compact(
            'adjustments',
            'summary',
            'departments',
            'locations',
            'startDate',
            'endDate',
            'departmentId',
            'locationId',
            'perPage'
        ));
    }
        
    /**
     * ABC Analysis Report with Pure Eloquent
     */
    public function abcAnalysis(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        $perPage = (int)$request->get('per_page', 15);
        
        // Get inventory items with relationships
        $query = InventoryItems::with([
            'variant.product',
            'departmentItem',
            'itemLocation'
        ])
        ->where('tenant_id', $tenantId)
        ->where('quantity_on_hand', '>', 0);
        
        // Apply department filter
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        // Apply location filter
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        // Get ALL inventory items for calculations (unpaginated)
        $allItems = $query->get();
        
        // Get inventory logs for movement data
        $logs = SingleShopInventoryLog::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->get()
            ->groupBy('variant_id');
        
        // Calculate inventory value and movement for each item using collections
        $itemsWithMetrics = $allItems->map(function ($item) use ($logs) {
            $costPrice = $item->variant->cost_price ?? 0;
            $inventoryValue = $item->quantity_on_hand * $costPrice;
            
            $itemLogs = $logs->get($item->variant_id, collect());
            $totalMovement = $itemLogs->sum(function ($log) {
                return abs($log->quantity_change);
            });
            
            $item->inventory_value = $inventoryValue;
            $item->total_movement = $totalMovement;
            
            return $item;
        });
        
        // Calculate total inventory value
        $totalValue = $itemsWithMetrics->sum('inventory_value');
        
        // Sort by inventory value and calculate cumulative percentage
        $sortedItems = $itemsWithMetrics->sortByDesc('inventory_value')->values();
        $cumulativePercentage = 0;
        
        $abcCategories = [
            'A' => ['items' => [], 'percentage' => 0, 'value' => 0, 'count' => 0],
            'B' => ['items' => [], 'percentage' => 0, 'value' => 0, 'count' => 0],
            'C' => ['items' => [], 'percentage' => 0, 'value' => 0, 'count' => 0],
        ];
        
        foreach ($sortedItems as $item) {
            $percentage = $totalValue > 0 ? ($item->inventory_value / $totalValue) * 100 : 0;
            $cumulativePercentage += $percentage;
            
            if ($cumulativePercentage <= 80) {
                $category = 'A';
            } elseif ($cumulativePercentage <= 95) {
                $category = 'B';
            } else {
                $category = 'C';
            }
            
            $item->abc_category = $category;
            $item->value_percentage = $percentage;
            $item->cumulative_percentage = $cumulativePercentage;
            
            $abcCategories[$category]['items'][] = $item;
            $abcCategories[$category]['value'] += $item->inventory_value;
            $abcCategories[$category]['count']++;
        }
        
        // Calculate percentages for each category
        foreach ($abcCategories as $category => $data) {
            $abcCategories[$category]['percentage'] = $totalValue > 0 
                ? ($data['value'] / $totalValue) * 100 
                : 0;
        }
        
        // Get all items for the table (with pagination)
        $allItemsList = collect();
        foreach (['A', 'B', 'C'] as $category) {
            $allItemsList = $allItemsList->merge($abcCategories[$category]['items'] ?? []);
        }
        $sortedItemsList = $allItemsList->sortByDesc('inventory_value')->values();
        
        // Apply pagination
        $paginatedItems = $this->paginateCollection($sortedItemsList, $perPage, 'page');
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.abc-analysis', compact(
            'abcCategories',
            'totalValue',
            'departments',
            'locations',
            'paginatedItems',
            'sortedItemsList',
            'startDate',
            'endDate',
            'departmentId',
            'locationId',
            'perPage'
        ));
    }
            
    /**
     * Movement Analysis Report using Multi-Shop Inventory Data
     */
    public function movementAnalysis(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $movementType = $request->get('movement_type', 'all');
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        $perPage = (int)$request->get('per_page', 15);
        
        // Get inventory transactions (sales, purchases, adjustments, transfers)
        $query = InventoryTransactions::with([
            'inventoryItems.variant.product',
            'inventoryItems.departmentItem',
            'inventoryItems.itemLocation'
        ])
        ->where('tenant_id', $tenantId)
        ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
        ->where('type', 'sale'); // Focus on sales movements
        
        // Apply department filter
        if ($departmentId) {
            $query->whereHas('inventoryItems', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }
        
        // Apply location filter
        if ($locationId) {
            $query->whereHas('inventoryItems', function($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        }
        
        // Get all transactions for the period
        $allTransactions = $query->get();
        
        // Group by variant_id through inventory_items
        $transactionsByVariant = $allTransactions->groupBy(function($transaction) {
            return $transaction->inventoryItems->variant_id ?? null;
        })->filter();
        
        $daysInPeriod = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        
        // Build movement data collection
        $movementCollection = collect();
        
        foreach ($transactionsByVariant as $variantId => $variantTransactions) {
            // Get variant details
            $firstTransaction = $variantTransactions->first();
            $inventoryItem = $firstTransaction->inventoryItems;
            $variant = $inventoryItem ? $inventoryItem->variant : null;
            
            if (!$variant) {
                continue;
            }
            
            $totalMovement = $variantTransactions->sum(function($transaction) {
                return abs($transaction->quantity);
            });
            
            $transactionCount = $variantTransactions->count();
            $firstMovement = $variantTransactions->min('created_at');
            $lastMovement = $variantTransactions->max('created_at');
            $avgDailyMovement = $daysInPeriod > 0 ? $totalMovement / $daysInPeriod : 0;
            
            // Calculate days since last movement
            $daysSinceLastMovement = $lastMovement ? Carbon::parse($lastMovement)->diffInDays(now()) : 999;
            
            // Determine movement category
            if ($avgDailyMovement >= 10) {
                $movementCategory = 'fast';
                $movementLabel = __('pagination.fast_moving');
                $movementColor = 'success';
                $movementIcon = 'ki-rocket';
            } elseif ($avgDailyMovement >= 1) {
                $movementCategory = 'slow';
                $movementLabel = __('pagination.slow_moving');
                $movementColor = 'warning';
                $movementIcon = 'ki-walk';
            } else {
                $movementCategory = 'non-moving';
                $movementLabel = __('pagination.non_moving');
                $movementColor = 'danger';
                $movementIcon = 'ki-pause-circle';
            }
            
            // Get current stock from inventory
            $currentStock = $inventoryItem ? $inventoryItem->quantity_on_hand : 0;
            
            $movementCollection->push((object)[
                'variant' => $variant,
                'variant_id' => $variantId,
                'inventory_item' => $inventoryItem,
                'total_movement' => $totalMovement,
                'transaction_count' => $transactionCount,
                'first_movement' => $firstMovement,
                'last_movement' => $lastMovement,
                'avg_daily_movement' => $avgDailyMovement,
                'days_since_last_movement' => $daysSinceLastMovement,
                'current_stock' => $currentStock,
                'movement_category' => $movementCategory,
                'movement_label' => $movementLabel,
                'movement_color' => $movementColor,
                'movement_icon' => $movementIcon,
            ]);
        }
        
        // Sort by total movement (descending)
        $sortedMovement = $movementCollection->sortByDesc('total_movement')->values();
        
        // Calculate movement statistics
        $movementStats = [
            'fast_moving' => $sortedMovement->where('movement_category', 'fast')->count(),
            'slow_moving' => $sortedMovement->where('movement_category', 'slow')->count(),
            'non_moving' => $sortedMovement->where('movement_category', 'non-moving')->count(),
            'total_items' => $sortedMovement->count(),
            'total_movement' => $sortedMovement->sum('total_movement'),
            'avg_daily_movement' => $sortedMovement->avg('avg_daily_movement'),
        ];
        
        // Filter by movement type if specified
        if ($movementType !== 'all') {
            $filteredMovement = $sortedMovement->filter(function ($item) use ($movementType) {
                return $item->movement_category === $movementType;
            })->values();
        } else {
            $filteredMovement = $sortedMovement;
        }
        
        // Apply pagination
        $movementData = $this->paginateCollection($filteredMovement, $perPage, 'page');
        
        // Get departments and locations for filters
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.movement-analysis', compact(
            'movementData',
            'movementStats',
            'sortedMovement',
            'departments',
            'locations',
            'startDate',
            'endDate',
            'movementType',
            'departmentId',
            'locationId',
            'perPage'
        ));
    }
            
    
    public function getMovementLogs(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        // Use request dates or default to last 90 days
        $startDate = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        
        $query = SingleShopInventoryLog::where('tenant_id', $tenantId)
            ->where('variant_id', $request->variant_id);
        
        // Apply date filter
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                $startDate . ' 00:00:00', 
                $endDate . ' 23:59:59'
            ]);
        }
        
        $logs = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'logs' => $logs
        ]);
    }


    
    /**
     * Helper method to calculate low stock value at risk
     */
    private function calculateLowStockValue($query)
    {
        return $query->with(['variant'])
            ->get()
            ->sum(function ($item) {
                $shortage = ($item->reorder_point ?: $item->preferred_stock_level * 0.5) - $item->quantity_on_hand;
                return max(0, $shortage) * ($item->variant->cost_price ?? 0);
            });
    }

    /**
     * Inventory Valuation Report with Proper Pagination
     */
    public function valuation(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        $valuationMethod = $request->get('valuation_method', 'cost');
        $perPage = (int)$request->get('per_page', 15);
        
        // Build query for inventory items
        $query = InventoryItems::with([
            'variant.product',
            'departmentItem',
            'itemLocation'
        ])
        ->where('tenant_id', $tenantId)
        ->where('quantity_on_hand', '>', 0);
        
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        // Get ALL inventory items for summary calculations (unpaginated)
        $allItems = $query->get();
        
        // Calculate valuation metrics for ALL items using collections
        $allItemsWithValuation = $allItems->map(function ($item) use ($valuationMethod) {
            $costPrice = $item->variant->cost_price ?? 0;
            $sellingPrice = $item->variant->price ?? 0;
            $quantity = $item->quantity_on_hand;
            
            // Calculate item value based on valuation method
            $itemValue = $quantity * $costPrice;
            $potentialProfit = $quantity * ($sellingPrice - $costPrice);
            $profitMargin = $costPrice > 0 ? (($sellingPrice - $costPrice) / $costPrice) * 100 : 0;
            
            $item->valuation_value = $itemValue;
            $item->potential_profit = $potentialProfit;
            $item->profit_margin = $profitMargin;
            
            return $item;
        });
        
        // Calculate valuation summary from ALL items
        $totalValue = $allItemsWithValuation->sum('valuation_value');
        $totalQuantity = $allItemsWithValuation->sum('quantity_on_hand');
        $totalProfit = $allItemsWithValuation->sum('potential_profit');
        
        $valuationSummary = [
            'total_items' => $allItemsWithValuation->count(),
            'total_quantity' => $totalQuantity,
            'total_value' => $totalValue,
            'avg_unit_cost' => $totalQuantity > 0 ? $totalValue / $totalQuantity : 0,
            'avg_unit_price' => $allItemsWithValuation->avg(function($item) {
                return $item->variant->price ?? 0;
            }),
            'potential_profit' => $totalProfit,
            'avg_profit_margin' => $allItemsWithValuation->avg('profit_margin'),
        ];
        
        // Calculate value by department
        $valueByDepartment = $allItemsWithValuation->groupBy('department_id')->map(function ($items, $deptId) {
            $department = $items->first()->departmentItem ?? null;
            $departmentName = $department ? $department->name : 'Unspecified';
            return [
                'name' => $departmentName,
                'value' => $items->sum('valuation_value'),
                'count' => $items->count()
            ];
        })->sortByDesc('value')->values();
        
        // Calculate value by location
        $valueByLocation = $allItemsWithValuation->groupBy('location_id')->map(function ($items, $locId) {
            $location = $items->first()->itemLocation ?? null;
            $locationName = $location ? $location->name : 'Unspecified';
            return [
                'name' => $locationName,
                'value' => $items->sum('valuation_value'),
                'count' => $items->count()
            ];
        })->sortByDesc('value')->values();
        
        // Apply pagination to the items collection
        $sortedItems = $allItemsWithValuation->sortByDesc('valuation_value')->values();
        $inventoryItems = $this->paginateCollection($sortedItems, $perPage, 'page');
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.valuation', compact(
            'inventoryItems',
            'valuationSummary',
            'valueByDepartment',
            'valueByLocation',
            'departments',
            'locations',
            'departmentId',
            'locationId',
            'valuationMethod',
            'perPage'
        ));
    }


    /**
     * Dead Stock Report with Pure Eloquent - Fixed
     */
    public function deadStock(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        $daysThreshold = (int)$request->get('days_threshold', 180);
        $includeExpired = $request->get('include_expired', false);
        $perPage = (int)$request->get('per_page', 15);
        
        // Get all inventory items with stock
        $allItems = InventoryItems::with([
            'variant.product',
            'departmentItem',
            'itemLocation'
        ])
        ->where('tenant_id', $tenantId)
        ->where('quantity_on_hand', '>', 0)
        ->when($departmentId, function($q, $departmentId) {
            return $q->where('department_id', $departmentId);
        })
        ->when($locationId, function($q, $locationId) {
            return $q->where('location_id', $locationId);
        })
        ->get();
        
        // Get inventory transactions for movement data (sales only for dead stock calculation)
        $transactions = InventoryTransactions::where('tenant_id', $tenantId)
            ->where('type', 'sale')
            ->where('quantity', '<', 0) // Negative quantity = outgoing
            ->get()
            ->groupBy(function($transaction) {
                return $transaction->inventoryItems->variant_id ?? null;
            });
        
        $deadStockCollection = collect();
        
        foreach ($allItems as $item) {
            $variantTransactions = $transactions->get($item->variant_id, collect());
            
            // Calculate last movement date from sales transactions
            $lastMovementDate = $variantTransactions->isNotEmpty() 
                ? $variantTransactions->max('created_at') 
                : $item->created_at;
            
            // Calculate total movement (quantity sold)
            $totalMovement = $variantTransactions->sum(function($transaction) {
                return abs($transaction->quantity);
            });
            
            $daysIdle = Carbon::parse($lastMovementDate)->diffInDays(now());
            $isExpired = $item->expiry_date && Carbon::parse($item->expiry_date)->lt(now());
            $inventoryValue = $item->quantity_on_hand * ($item->variant->cost_price ?? 0);
            
            // Check if item qualifies as dead stock
            $isDeadStock = $daysIdle >= $daysThreshold;
            
            // Include expired items if checkbox is checked
            if ($includeExpired && $isExpired) {
                $isDeadStock = true;
            }
            
            if ($isDeadStock) {
                $deadStockCollection->push((object)[
                    'id' => $item->id,
                    'variant' => $item->variant,
                    'departmentItem' => $item->departmentItem,
                    'itemLocation' => $item->itemLocation,
                    'quantity_on_hand' => $item->quantity_on_hand,
                    'expiry_date' => $item->expiry_date,
                    'last_movement_date' => $lastMovementDate,
                    'total_movement' => $totalMovement,
                    'days_idle' => $daysIdle,
                    'is_expired' => $isExpired,
                    'inventory_value' => $inventoryValue,
                    'cost_price' => $item->variant->cost_price ?? 0,
                    'sku' => $item->variant->sku ?? '-',
                    'barcode' => $item->variant->barcode ?? '-',
                    'variant_name' => $item->variant->name ?? '-',
                    'product_name' => $item->variant->product->name ?? '',
                    'image_url' => $item->variant->image_url ?? null,
                    'department_name' => $item->departmentItem->name ?? '-',
                    'location_name' => $item->itemLocation->name ?? '-',
                ]);
            }
        }
        
        // Sort by days idle (most idle first)
        $sortedDeadStock = $deadStockCollection->sortByDesc('days_idle')->values();
        
        // Calculate summary
        $summary = [
            'total_items' => $sortedDeadStock->count(),
            'total_quantity' => $sortedDeadStock->sum('quantity_on_hand'),
            'total_value' => $sortedDeadStock->sum('inventory_value'),
            'avg_days_idle' => $sortedDeadStock->avg('days_idle') ?? 0,
            'expired_items' => $sortedDeadStock->where('is_expired', true)->count(),
        ];
        
        // Idle categories
        $idleCategories = [
            '180_365' => $sortedDeadStock->filter(function($item) {
                return $item->days_idle >= 180 && $item->days_idle < 365;
            })->sum('quantity_on_hand'),
            '365_730' => $sortedDeadStock->filter(function($item) {
                return $item->days_idle >= 365 && $item->days_idle < 730;
            })->sum('quantity_on_hand'),
            'over_730' => $sortedDeadStock->filter(function($item) {
                return $item->days_idle >= 730;
            })->sum('quantity_on_hand'),
        ];
        
        // Dead stock by department for chart
        $departmentDeadStock = $sortedDeadStock->groupBy(function($item) {
            return $item->department_name;
        })->map(function($items, $name) {
            return [
                'name' => $name,
                'quantity' => $items->sum('quantity_on_hand'),
                'value' => $items->sum('inventory_value')
            ];
        })->sortByDesc('quantity')->values();
        
        // Apply pagination
        $deadStockItems = $this->paginateCollection($sortedDeadStock, $perPage, 'page');
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        // Debug - check if there's any dead stock
        \Log::info('Dead Stock Report:', [
            'total_items_checked' => $allItems->count(),
            'dead_stock_found' => $sortedDeadStock->count(),
            'days_threshold' => $daysThreshold,
            'include_expired' => $includeExpired
        ]);
        
        return view('reports.inventory.dead-stock', compact(
            'deadStockItems',
            'summary',
            'idleCategories',
            'departmentDeadStock',
            'departments',
            'locations',
            'departmentId',
            'locationId',
            'daysThreshold',
            'includeExpired',
            'perPage'
        ));
    }
        
    /**
     * Excess Stock Report with Pure Eloquent
     */
    public function excessStock(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        $excessThreshold = (float)$request->get('excess_threshold', 1.5);
        $perPage = (int)$request->get('per_page', 15);
        
        // Get all inventory items with preferred stock level
        $allItems = InventoryItems::with([
            'variant.product',
            'departmentItem',
            'itemLocation'
        ])
        ->where('tenant_id', $tenantId)
        ->where('quantity_on_hand', '>', 0)
        ->where('preferred_stock_level', '>', 0)
        ->when($departmentId, function($q, $departmentId) {
            return $q->where('department_id', $departmentId);
        })
        ->when($locationId, function($q, $locationId) {
            return $q->where('location_id', $locationId);
        })
        ->get();
        
        // Calculate excess stock metrics using collections
        $excessCollection = collect();
        
        foreach ($allItems as $item) {
            $currentStock = $item->quantity_on_hand;
            $preferredStock = $item->preferred_stock_level;
            $thresholdStock = $preferredStock * $excessThreshold;
            $excessQuantity = max(0, $currentStock - $thresholdStock);
            
            if ($excessQuantity > 0) {
                $excessPercentage = (($currentStock / $preferredStock) - 1) * 100;
                $costPrice = $item->variant->cost_price ?? 0;
                $excessValue = $excessQuantity * $costPrice;
                
                // Determine severity
                if ($excessPercentage >= 200) {
                    $severity = 'critical';
                    $severityColor = 'danger';
                    $severityIcon = 'ki-cross';
                    $severityText = __('pagination.critical');
                } elseif ($excessPercentage >= 100) {
                    $severity = 'high';
                    $severityColor = 'danger';
                    $severityIcon = 'ki-danger';
                    $severityText = __('pagination.high');
                } elseif ($excessPercentage >= 50) {
                    $severity = 'moderate';
                    $severityColor = 'warning';
                    $severityIcon = 'ki-warning-2';
                    $severityText = __('pagination.moderate');
                } else {
                    $severity = 'low';
                    $severityColor = 'info';
                    $severityIcon = 'ki-information';
                    $severityText = __('pagination.low');
                }
                
                $excessCollection->push((object)[
                    'id' => $item->id,
                    'variant' => $item->variant,
                    'departmentItem' => $item->departmentItem,
                    'itemLocation' => $item->itemLocation,
                    'quantity_on_hand' => $currentStock,
                    'preferred_stock_level' => $preferredStock,
                    'threshold_stock' => $thresholdStock,
                    'excess_quantity' => $excessQuantity,
                    'excess_percentage' => $excessPercentage,
                    'excess_value' => $excessValue,
                    'cost_price' => $costPrice,
                    'severity' => $severity,
                    'severity_color' => $severityColor,
                    'severity_icon' => $severityIcon,
                    'severity_text' => $severityText,
                    'sku' => $item->variant->sku ?? '-',
                    'barcode' => $item->variant->barcode ?? '-',
                    'variant_name' => $item->variant->name ?? '-',
                    'product_name' => $item->variant->product->name ?? '',
                    'image_url' => $item->variant->image_url ?? null,
                    'department_name' => $item->departmentItem->name ?? '-',
                    'location_name' => $item->itemLocation->name ?? '-',
                ]);
            }
        }
        
        // Sort by excess quantity (highest first)
        $sortedExcess = $excessCollection->sortByDesc('excess_quantity')->values();
        
        // Calculate summary
        $summary = [
            'total_items' => $sortedExcess->count(),
            'total_excess_quantity' => $sortedExcess->sum('excess_quantity'),
            'total_excess_value' => $sortedExcess->sum('excess_value'),
            'avg_excess_percentage' => $sortedExcess->avg('excess_percentage') ?? 0,
        ];
        
        // Categorize by excess percentage
        $excessCategories = [
            '50_100' => $sortedExcess->filter(function($item) {
                return $item->excess_percentage >= 50 && $item->excess_percentage < 100;
            })->sum('excess_quantity'),
            '100_200' => $sortedExcess->filter(function($item) {
                return $item->excess_percentage >= 100 && $item->excess_percentage < 200;
            })->sum('excess_quantity'),
            'over_200' => $sortedExcess->filter(function($item) {
                return $item->excess_percentage >= 200;
            })->sum('excess_quantity'),
        ];
        
        // Apply pagination
        $excessStockItems = $this->paginateCollection($sortedExcess, $perPage, 'page');
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.excess-stock', compact(
            'excessStockItems',
            'summary',
            'excessCategories',
            'departments',
            'locations',
            'departmentId',
            'locationId',
            'excessThreshold',
            'perPage'
        ));
    }
        
    /**
     * Helper method to calculate dead stock value
     */
    private function calculateDeadStockValue($items)
    {
        return $items->sum(function ($item) {
            return $item->quantity_on_hand * ($item->variant->cost_price ?? 0);
        });
    }

    /**
     * Helper method to calculate excess stock value
     */
    private function calculateExcessStockValue($items, $threshold)
    {
        return $items->sum(function ($item) use ($threshold) {
            $costPrice = $item->variant->cost_price ?? 0;
            $excessQuantity = max(0, $item->quantity_on_hand - ($item->preferred_stock_level * $threshold));
            return $excessQuantity * $costPrice;
        });
    }
    
}