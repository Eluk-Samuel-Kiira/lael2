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
use App\Models\BatchLog;
use App\Models\PurchaseReceiptItem;
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
        if (!$user->hasPermissionTo('inventory reports')) {
            abort(403, __('payments.not_authorized'));
        }
        
        $tenantId = $user->tenant_id;

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
            ->where('tenant_id', $tenantId);
        
        // Apply date filter
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }
        
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
            'total_quantity_on_hand' => $allItems->sum('quantity_on_hand'),
            'total_quantity_allocated' => $allItems->sum('quantity_allocated'),
            'total_value' => $this->calculateInventoryValueFromCollection($allItems),
            'average_stock_level' => $allItems->avg('quantity_on_hand'),
            'items_below_reorder' => $allItems->filter(function($item) {
                return $item->quantity_on_hand < ($item->reorder_point ?? 0);
            })->count(),
            'items_above_preferred' => $allItems->filter(function($item) {
                return $item->preferred_stock_level > 0 && $item->quantity_on_hand > $item->preferred_stock_level;
            })->count(),
            'out_of_stock' => $allItems->where('quantity_on_hand', 0)->count(),
            'total_reorder_point' => $allItems->sum('reorder_point'),
            'total_preferred_stock' => $allItems->sum('preferred_stock_level'),
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
     * Inventory Transactions Report with Variant Filtering
     */
    public function transactions(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $type = $request->get('type', 'all');
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        $variantId = $request->get('variant_id'); // ✅ Added variant filter
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
        
        // ✅ Add variant filter
        if ($variantId) {
            $query->whereHas('InventoryItems', function($q) use ($variantId) {
                $q->where('variant_id', $variantId);
            });
        }
        
        // ✅ DEBUG: Check first transaction
        $firstTransaction = $query->first();
        if ($firstTransaction) {
            // \Log::info('Transaction Debug:', [
            //     'transaction_id' => $firstTransaction->id,
            //     'inventory_id' => $firstTransaction->inventory_id,
            //     'has_inventory_item' => $firstTransaction->InventoryItems ? 'Yes' : 'No',
            //     'inventory_item_exists' => $firstTransaction->InventoryItems ? $firstTransaction->InventoryItems->id : 'N/A',
            //     'variant_id_from_inventory' => $firstTransaction->InventoryItems ? $firstTransaction->InventoryItems->variant_id : 'N/A',
            // ]);
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
        
        // ✅ Get variants for filter dropdown
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get();
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.transactions', compact(
            'transactions',
            'typeSummary',
            'dailyTrend',
            'departments',
            'locations',
            'variants', // ✅ Pass variants to view
            'startDate',
            'endDate',
            'type',
            'departmentId',
            'locationId',
            'variantId', // ✅ Pass variant ID
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
     * Inventory Turnover Report with Pure Eloquent - Supports Both Single & Multi-Shop
     */
    public function turnover(Request $request)
    {
        $tenantId = $this->getTenantId();
        $isSingleShop = tenant_is_single_shop($tenantId);
        
        $startDate = $request->get('start_date', now()->subMonths(6)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $variantId = $request->get('variant_id');
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
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
        
        // ─── Get Movement Data Based on Shop Type ──────────────────────
        $movementData = collect();
        
        if ($isSingleShop) {
            // ─── Single Shop: Use SingleShopInventoryLog ────────────────
            $logs = SingleShopInventoryLog::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->get();
            
            // Group logs by variant_id
            $logsByVariant = $logs->groupBy('variant_id');
            
            foreach ($allVariants as $variant) {
                $variantLogs = $logsByVariant->get($variant->id, collect());
                
                if ($variantLogs->isEmpty()) {
                    continue;
                }
                
                $totalMovement = $variantLogs->sum(function ($log) {
                    return abs($log->quantity_change);
                });
                
                $avgStockLevel = $variantLogs->avg('quantity_before') ?: 1;
                $transactionCount = $variantLogs->count();
                $firstMovement = $variantLogs->min('created_at');
                $lastMovement = $variantLogs->max('created_at');
                
                $movementData->push([
                    'variant' => $variant,
                    'total_movement' => $totalMovement,
                    'avg_stock_level' => $avgStockLevel,
                    'transaction_count' => $transactionCount,
                    'first_movement' => $firstMovement,
                    'last_movement' => $lastMovement,
                ]);
            }
            
        } else {
            // ─── Multi-Shop: Use InventoryTransactions ──────────────────
            $transactionsQuery = InventoryTransactions::with(['InventoryItems' => function($q) use ($departmentId, $locationId) {
                if ($departmentId) {
                    $q->where('department_id', $departmentId);
                }
                if ($locationId) {
                    $q->where('location_id', $locationId);
                }
            }])
            ->where('tenant_id', $tenantId)
            ->where('type', 'sale') // Only sales
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            
            // Apply department filter if provided
            if ($departmentId) {
                $transactionsQuery->whereHas('InventoryItems', function($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                });
            }
            
            // Apply location filter if provided
            if ($locationId) {
                $transactionsQuery->whereHas('InventoryItems', function($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                });
            }
            
            $transactions = $transactionsQuery->get();
            
            // Group by variant_id through inventory_items
            $transactionsByVariant = $transactions->groupBy(function($transaction) {
                return $transaction->InventoryItems->variant_id ?? null;
            });
            
            foreach ($allVariants as $variant) {
                $variantTransactions = $transactionsByVariant->get($variant->id, collect());
                
                if ($variantTransactions->isEmpty()) {
                    continue;
                }
                
                $totalMovement = $variantTransactions->sum(function ($transaction) {
                    return abs($transaction->quantity);
                });
                
                // Get average stock level from InventoryItems
                $inventoryItem = InventoryItems::where('variant_id', $variant->id)
                    ->where('tenant_id', $tenantId);
                
                if ($departmentId) {
                    $inventoryItem->where('department_id', $departmentId);
                }
                if ($locationId) {
                    $inventoryItem->where('location_id', $locationId);
                }
                
                $avgStockLevel = $inventoryItem->avg('quantity_on_hand') ?: 1;
                $transactionCount = $variantTransactions->count();
                $firstMovement = $variantTransactions->min('created_at');
                $lastMovement = $variantTransactions->max('created_at');
                
                $movementData->push([
                    'variant' => $variant,
                    'total_movement' => $totalMovement,
                    'avg_stock_level' => $avgStockLevel,
                    'transaction_count' => $transactionCount,
                    'first_movement' => $firstMovement,
                    'last_movement' => $lastMovement,
                ]);
            }
        }
        
        // ─── Calculate Turnover Metrics ─────────────────────────────────
        $turnoverCollection = $movementData->map(function ($data) use ($daysInPeriod) {
            $variant = $data['variant'];
            $totalMovement = $data['total_movement'];
            $avgStockLevel = $data['avg_stock_level'] ?: 1;
            $transactionCount = $data['transaction_count'];
            $firstMovement = $data['first_movement'];
            $lastMovement = $data['last_movement'];
            
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
        });
        
        // Sort by turnover rate (descending)
        $sortedTurnover = $turnoverCollection->sortByDesc('turnover_rate')->values();
        
        // ✅ Apply pagination
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
        
        // Get departments and locations for filters
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.turnover', compact(
            'turnoverData',
            'sortedTurnover',
            'summary',
            'variants',
            'departments',
            'locations',
            'startDate',
            'endDate',
            'variantId',
            'departmentId',
            'locationId',
            'perPage',
            'daysInPeriod',
            'isSingleShop'
        ));
    }


    /**
     * ✅ FIXED: Stock Aging Report with Proper Prices and Batch Tracking
     */
    public function stockAging(Request $request)
    {
        $tenantId = $this->getTenantId();
        $isSingleShop = tenant_is_single_shop($tenantId); 
        
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        $variantId = $request->get('variant_id');
        $category = $request->get('category');
        $perPage = (int)$request->get('per_page', 15);
        
        // ─── Get all inventory items with relationships ──────────────
        $allItems = InventoryItems::with([
            'variant.product',
            'departmentItem',
            'itemLocation'
        ])
        ->where('tenant_id', $tenantId)
        ->where('quantity_allocated', '>', 0)
        ->when($departmentId, function($q, $departmentId) {
            return $q->where('department_id', $departmentId);
        })
        ->when($locationId, function($q, $locationId) {
            return $q->where('location_id', $locationId);
        })
        ->when($variantId, function($q, $variantId) {
            return $q->where('variant_id', $variantId);
        })
        ->get();
        
        // ─── Get Batch Logs for batch-based items ─────────────────────
        $batchLogs = BatchLog::where('tenant_id', $tenantId)
            ->whereIn('variant_id', $allItems->pluck('variant_id')->filter())
            ->orderBy('event_date', 'desc')
            ->get()
            ->groupBy('variant_id');
        
        // ─── Get Purchase Receipt Items for batch details ─────────────
        // ✅ FIXED: tenant_id is on PurchaseOrder, filter through that relationship
        $batchNumbers = $allItems->pluck('batch_number')->filter()->values();
        $batchItems = collect();
        
        if ($batchNumbers->isNotEmpty()) {
            $batchItems = PurchaseReceiptItem::with([
                'purchaseReceipt.purchaseOrder' // Eager load receipt -> order
            ])
            ->whereIn('batch_number', $batchNumbers)
            ->get()
            ->filter(function($item) use ($tenantId) {
                // Filter by tenant through: item -> purchaseReceipt -> purchaseOrder
                return $item->purchaseReceipt 
                    && $item->purchaseReceipt->purchaseOrder 
                    && $item->purchaseReceipt->purchaseOrder->tenant_id == $tenantId;
            })
            ->groupBy('batch_number');
        }
        
        // ─── Calculate aging metrics for each item ─────────────────────
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
        $totalValueAtRisk = 0;
        $totalInventoryValue = 0;
        
        foreach ($allItems as $item) {
            // ─── Get variant with proper pricing ────────────────────────
            $variant = $item->variant;
            
            // ✅ Use grand_total_cost_price for accurate cost
            $costPrice = $variant->grand_total_cost_price ?? 0;
            $sellingPrice = $variant->selling_price ?? 0;
            
            // ─── Get Days Since Last Movement ──────────────────────────
            $daysSinceLastMovement = $this->getDaysSinceLastMovement($item, $isSingleShop);
            
            // ─── Get Expiry Date ────────────────────────────────────────
            $expiryDate = null;
            $batchNumber = $item->batch_number;
            
            if ($item->expiry_date) {
                $expiryDate = Carbon::parse($item->expiry_date);
            } elseif ($batchNumber && $batchItems->has($batchNumber)) {
                $batchInfo = $batchItems->get($batchNumber)->first();
                if ($batchInfo && $batchInfo->expiry_date) {
                    $expiryDate = Carbon::parse($batchInfo->expiry_date);
                }
            }
            
            $daysToExpiry = $expiryDate ? $today->diffInDays($expiryDate, false) : null;
            
            // ─── Get batch logs for this variant ────────────────────────
            $variantBatchLogs = $batchLogs->get($item->variant_id, collect());
            $lastBatchLog = $variantBatchLogs->first();
            
            // ─── Determine Aging Category ──────────────────────────────
            if ($expiryDate && $daysToExpiry !== null) {
                if ($daysToExpiry < 0) {
                    $categoryKey = 'expired';
                    $statusColor = 'danger';
                    $statusText = __('pagination.expired');
                    $progressColor = 'danger';
                    $urgency = 'immediate';
                } elseif ($daysToExpiry <= 7) {
                    $categoryKey = '1_week';
                    $statusColor = 'warning';
                    $statusText = __('pagination.expires_in_1_week');
                    $progressColor = 'warning';
                    $urgency = 'critical';
                } elseif ($daysToExpiry <= 30) {
                    $categoryKey = '1_month';
                    $statusColor = 'warning';
                    $statusText = __('pagination.expires_in_1_month');
                    $progressColor = 'warning';
                    $urgency = 'high';
                } elseif ($daysToExpiry <= 90) {
                    $categoryKey = '3_months';
                    $statusColor = 'info';
                    $statusText = __('pagination.expires_in_3_months');
                    $progressColor = 'info';
                    $urgency = 'medium';
                } elseif ($daysToExpiry <= 180) {
                    $categoryKey = '6_months';
                    $statusColor = 'success';
                    $statusText = __('pagination.expires_in_6_months');
                    $progressColor = 'success';
                    $urgency = 'low';
                } else {
                    $categoryKey = 'over_6_months';
                    $statusColor = 'primary';
                    $statusText = __('pagination.expires_after_6_months');
                    $progressColor = 'primary';
                    $urgency = 'none';
                }
            } else {
                // No expiry date - use last movement
                if ($daysSinceLastMovement > 180) {
                    $categoryKey = 'over_6_months';
                    $statusColor = 'warning';
                    $statusText = __('pagination.no_movement_6_months');
                    $progressColor = 'warning';
                    $urgency = 'medium';
                } elseif ($daysSinceLastMovement > 90) {
                    $categoryKey = '3_months';
                    $statusColor = 'info';
                    $statusText = __('pagination.no_movement_3_months');
                    $progressColor = 'info';
                    $urgency = 'low';
                } else {
                    $categoryKey = '6_months';
                    $statusColor = 'success';
                    $statusText = __('pagination.active');
                    $progressColor = 'success';
                    $urgency = 'none';
                }
            }
            
            // ✅ Use quantity_allocated for stock
            $quantity = $item->quantity_allocated;
            
            // Add to category totals
            $agingCategories[$categoryKey] += $quantity;
            
            // Filter by category if specified
            if ($category && $category !== $categoryKey) {
                continue;
            }
            
            // ✅ Calculate inventory value using grand_total_cost_price
            $inventoryValue = $quantity * $costPrice;
            $totalInventoryValue += $inventoryValue;
            
            // Calculate value at risk (expired or near expiry)
            $valueAtRisk = ($categoryKey === 'expired' || $categoryKey === '1_week' || $categoryKey === '1_month') 
                ? $inventoryValue 
                : 0;
            
            if ($valueAtRisk > 0) {
                $totalValueAtRisk += $valueAtRisk;
            }
            
            // ─── Build the aging item object ────────────────────────────
            $agingCollection->push((object)[
                'id' => $item->id,
                'variant' => $variant,
                'departmentItem' => $item->departmentItem,
                'itemLocation' => $item->itemLocation,
                
                // ✅ Use quantity_allocated
                'quantity_allocated' => $quantity,
                'quantity_on_hand' => $item->quantity_on_hand,
                
                // Batch information
                'batch_number' => $batchNumber ?? '-',
                'expiry_date' => $expiryDate ? $expiryDate->format('Y-m-d') : null,
                'is_batch_tracked' => !empty($batchNumber),
                
                // Aging metrics
                'days_to_expiry' => $daysToExpiry ?? $daysSinceLastMovement,
                'days_since_last_movement' => $daysSinceLastMovement,
                
                // ✅ Pricing using grand_total_cost_price
                'cost_price' => $costPrice,
                'selling_price' => $sellingPrice,
                'inventory_value' => $inventoryValue,
                'value_at_risk' => $valueAtRisk,
                'profit_margin' => $costPrice > 0 ? (($sellingPrice - $costPrice) / $costPrice) * 100 : 0,
                
                // Status and category
                'category_key' => $categoryKey,
                'status_color' => $statusColor,
                'status_text' => $statusText,
                'progress_color' => $progressColor,
                'urgency' => $urgency,
                
                // Variant details
                'sku' => $variant->sku ?? '-',
                'barcode' => $variant->barcode ?? '-',
                'variant_name' => $variant->name ?? '-',
                'product_name' => $variant->product->name ?? '',
                'image_url' => $variant->image_url ?? null,
                
                // Department and location
                'department_name' => $item->departmentItem->name ?? '-',
                'location_name' => $item->itemLocation->name ?? '-',
                
                // Batch log info
                'last_batch_event' => $lastBatchLog ? $lastBatchLog->event_date : null,
                'last_batch_event_type' => $lastBatchLog ? $lastBatchLog->type : null,
                'last_batch_quantity' => $lastBatchLog ? $lastBatchLog->quantity_after : null,
            ]);
        }
        
        // ─── Sort by urgency (most urgent first) ──────────────────────
        $urgencyOrder = ['immediate' => 1, 'critical' => 2, 'high' => 3, 'medium' => 4, 'low' => 5, 'none' => 6];
        $sortedAging = $agingCollection->sortBy(function($item) use ($urgencyOrder) {
            return $urgencyOrder[$item->urgency] ?? 99;
        })->values();
        
        // ─── Calculate summary ────────────────────────────────────────
        $summary = [
            'expired' => $agingCategories['expired'],
            '1_week' => $agingCategories['1_week'],
            '1_month' => $agingCategories['1_month'],
            '3_months' => $agingCategories['3_months'],
            '6_months' => $agingCategories['6_months'],
            'over_6_months' => $agingCategories['over_6_months'],
            'total_items' => $sortedAging->count(),
            'total_value_at_risk' => $totalValueAtRisk,
            'total_inventory_value' => $totalInventoryValue,
            'batch_tracked_items' => $sortedAging->where('is_batch_tracked', true)->count(),
            'expired_value' => $sortedAging->where('category_key', 'expired')->sum('inventory_value'),
            'avg_profit_margin' => $sortedAging->avg('profit_margin') ?? 0,
        ];
        
        // ─── Apply pagination ──────────────────────────────────────────
        $agingItems = $this->paginateCollection($sortedAging, $perPage, 'page');
        
        // ─── Get filter options ────────────────────────────────────────
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get();
        
        return view('reports.inventory.stock-aging', compact(
            'agingItems',
            'summary',
            'agingCategories',
            'departments',
            'locations',
            'variants',
            'departmentId',
            'locationId',
            'variantId',
            'category',
            'perPage'
        ));
    }


    /**
     * ✅ FIXED: Get days since last movement with batch support
     */
    private function getDaysSinceLastMovement($item, $isSingleShop)
    {
        $lastMovement = null;
        
        if ($isSingleShop) {
            // ─── Single Shop: Use SingleShopInventoryLog ──────────────
            $lastLog = SingleShopInventoryLog::where('variant_id', $item->variant_id)
                ->where('tenant_id', $item->tenant_id)
                ->latest('created_at')
                ->first();
            
            if ($lastLog) {
                $lastMovement = Carbon::parse($lastLog->created_at);
            }
        } else {
            // ─── Multi-Shop: Use InventoryTransactions ──────────────────
            $lastTransaction = InventoryTransactions::whereHas('InventoryItems', function($q) use ($item) {
                    $q->where('variant_id', $item->variant_id);
                })
                ->where('tenant_id', $item->tenant_id)
                ->latest('created_at')
                ->first();
            
            if ($lastTransaction) {
                $lastMovement = Carbon::parse($lastTransaction->created_at);
            }
        }
        
        // ─── If no movement found, check batch logs ──────────────────
        if (!$lastMovement) {
            $lastBatchLog = BatchLog::where('variant_id', $item->variant_id)
                ->where('tenant_id', $item->tenant_id)
                ->latest('event_date')
                ->first();
            
            if ($lastBatchLog) {
                $lastMovement = Carbon::parse($lastBatchLog->event_date);
            }
        }
        
        // ─── If still no movement, check inventory creation ──────────
        if (!$lastMovement && $item->created_at) {
            $lastMovement = Carbon::parse($item->created_at);
        }
        
        // ─── If no movement found at all ──────────────────────────────
        if (!$lastMovement) {
            return 999; // Assume very old
        }
        
        return $lastMovement->diffInDays(now());
    }


    
    /**
     * Low Stock Alerts Report with Pure Eloquent - Supports Single & Multi-Shop
     */
    public function lowStockAlerts(Request $request)
    {
        $tenantId = $this->getTenantId();
        $isSingleShop = tenant_is_single_shop($tenantId);
        
        $departmentId = $request->get('department_id');
        $locationId = $request->get('location_id');
        $variantId = $request->get('variant_id');
        $severity = $request->get('severity', 'all');
        $perPage = (int)$request->get('per_page', 15);
        
        // ─── Get Inventory Items ──────────────────────────────────────────
        $allItems = InventoryItems::with([
            'variant.product',
            'departmentItem',
            'itemLocation'
        ])
        ->where('tenant_id', $tenantId)
        ->when($departmentId, function($q, $departmentId) {
            return $q->where('department_id', $departmentId);
        })
        ->when($locationId, function($q, $locationId) {
            return $q->where('location_id', $locationId);
        })
        ->when($variantId, function($q, $variantId) {
            return $q->where('variant_id', $variantId);
        })
        ->get();
        
        // ✅ Log the data for debugging
        // \Log::info('Low Stock Alerts - Total items found: ' . $allItems->count());
        // \Log::info('Low Stock Alerts - Sample item:', [
        //     'quantity_allocated' => $allItems->first()->quantity_allocated ?? 'N/A',
        //     'preferred_stock_level' => $allItems->first()->preferred_stock_level ?? 'N/A',
        //     'reorder_point' => $allItems->first()->reorder_point ?? 'N/A',
        // ]);
        
        // ─── Calculate Low Stock Metrics ──────────────────────────────────
        $lowStockItems = collect();
        
        foreach ($allItems as $item) {
            // ✅ Use quantity_allocated (this is the actual stock)
            $currentStock = $item->quantity_allocated ?? 0;
            $reorderPoint = $item->reorder_point ?? 0;
            $preferredStock = $item->preferred_stock_level ?? 0;
            
            // ✅ Determine if we should check this item
            $hasThreshold = false;
            $lowStockThreshold = 0;
            $isLowStock = false;
            $severityLevel = 'normal';
            $severityColor = 'success';
            $severityIcon = 'ki-check-circle';
            $deficit = 0;
            $deficitValue = 0;
            
            if ($isSingleShop) {
                // ─── Single Shop: Use variant.low_stock_level ────────────────
                $variant = $item->variant;
                if ($variant && isset($variant->low_stock_level) && $variant->low_stock_level > 0) {
                    $lowStockThreshold = $variant->low_stock_level;
                    $hasThreshold = true;
                }
            } else {
                // ─── Multi-Shop: Use preferred_stock_level or reorder_point ──
                if ($preferredStock > 0) {
                    $lowStockThreshold = $preferredStock;
                    $hasThreshold = true;
                } elseif ($reorderPoint > 0) {
                    $lowStockThreshold = $reorderPoint;
                    $hasThreshold = true;
                }
            }
            
            // ✅ If there's no threshold set, skip this item
            if (!$hasThreshold) {
                continue;
            }
            
            // ✅ Check if stock is below threshold
            if ($currentStock <= $lowStockThreshold) {
                $isLowStock = true;
                $deficit = $lowStockThreshold - $currentStock;
                $deficitValue = $deficit * ($item->variant->cost_price ?? 0);
                
                // Determine severity based on how low the stock is
                if ($currentStock <= $lowStockThreshold * 0.3) {
                    $severityLevel = 'critical';
                    $severityColor = 'danger';
                    $severityIcon = 'ki-shield-cross';
                } elseif ($currentStock <= $lowStockThreshold * 0.7) {
                    $severityLevel = 'warning';
                    $severityColor = 'warning';
                    $severityIcon = 'ki-shield-tick';
                } else {
                    $severityLevel = 'low';
                    $severityColor = 'info';
                    $severityIcon = 'ki-information';
                }
            }
            
            // ─── Filter by severity if specified ────────────────────────────
            if ($severity !== 'all' && $severityLevel !== $severity) {
                continue;
            }
            
            if ($isLowStock) {
                $reorderPercentage = $lowStockThreshold > 0 ? ($currentStock / $lowStockThreshold) * 100 : 0;
                
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
                
                // Determine stock source label
                $stockSource = $isSingleShop 
                    ? __('pagination.global_stock') 
                    : __('pagination.department_stock');
                
                $lowStockItems->push((object)[
                    'id' => $item->id,
                    'variant' => $item->variant,
                    'departmentItem' => $item->departmentItem,
                    'itemLocation' => $item->itemLocation,
                    'quantity_on_hand' => $currentStock,
                    'reorder_point' => $reorderPoint,
                    'preferred_stock_level' => $preferredStock,
                    'low_stock_threshold' => $lowStockThreshold,
                    'deficit' => $deficit,
                    'deficit_value' => $deficitValue,
                    'reorder_percentage' => $reorderPercentage,
                    'severity' => $severityLevel,
                    'severity_color' => $severityColor,
                    'severity_icon' => $severityIcon,
                    'severity_text' => $severityLevel === 'critical' ? __('pagination.critical') : ($severityLevel === 'warning' ? __('pagination.warning') : __('pagination.low')),
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
                    'is_single_shop' => $isSingleShop,
                    'stock_source' => $stockSource,
                ]);
            }
        }
        
        // ✅ Log how many low stock items were found
        // \Log::info('Low Stock Alerts - Low stock items found: ' . $lowStockItems->count());
        
        // Sort by severity (critical first, then warning)
        $severityOrder = ['critical' => 1, 'warning' => 2, 'low' => 3, 'normal' => 4];
        $sortedLowStock = $lowStockItems->sortBy(function($item) use ($severityOrder) {
            return $severityOrder[$item->severity] ?? 99;
        })->values();
        
        // ✅ Create paginator for the collection
        $currentPage = $request->input('page', 1);
        $perPage = (int)$request->input('per_page', 15);
        $total = $sortedLowStock->count();
        
        // Slice the collection for the current page
        $itemsForPage = $sortedLowStock->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        // Create a custom paginator
        $lowStockItems = new \Illuminate\Pagination\LengthAwarePaginator(
            $itemsForPage,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                'pageName' => 'page',
                'query' => $request->query(),
            ]
        );
        
        // Calculate summary
        $summary = [
            'critical' => $sortedLowStock->where('severity', 'critical')->count(),
            'warning' => $sortedLowStock->where('severity', 'warning')->count(),
            'low' => $sortedLowStock->where('severity', 'low')->count(),
            'total_items' => $sortedLowStock->count(),
            'total_value_at_risk' => $sortedLowStock->sum('deficit_value'),
            'is_single_shop' => $isSingleShop,
        ];
        
        // Get filter options
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get();
        
        return view('reports.inventory.low-stock-alerts', compact(
            'lowStockItems',
            'summary',
            'departments',
            'locations',
            'variants',
            'departmentId',
            'locationId',
            'variantId',
            'severity',
            'perPage',
            'isSingleShop'
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
        $variantId = $request->get('variant_id'); // ✅ Added
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
        
        // ✅ Add variant filter
        if ($variantId) {
            $query->whereHas('inventoryItems', function($q) use ($variantId) {
                $q->where('variant_id', $variantId);
            });
        }
        
        // Get ALL adjustments for summary calculations
        $allAdjustments = $query->get();
        
        // Calculate summary statistics
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
            'total_before' => $allAdjustments->sum('quantity_before'),
            'total_after' => $allAdjustments->sum('quantity_after'),
        ];
        
        // Get paginated adjustments for display
        $paginatedQuery = clone $query;
        $adjustments = $paginatedQuery->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
        
        // ✅ Get variants for filter dropdown
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get();
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.adjustments', compact(
            'adjustments',
            'summary',
            'departments',
            'locations',
            'variants',
            'startDate',
            'endDate',
            'departmentId',
            'locationId',
            'variantId',
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
        $variantId = $request->get('variant_id'); // ✅ Added variant filter
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
        
        // ✅ Apply variant filter
        if ($variantId) {
            $query->where('variant_id', $variantId);
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
        
        // ✅ Get variants for filter dropdown
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get();
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.abc-analysis', compact(
            'abcCategories',
            'totalValue',
            'departments',
            'locations',
            'variants', // ✅ Pass variants to view
            'paginatedItems',
            'sortedItemsList',
            'startDate',
            'endDate',
            'departmentId',
            'locationId',
            'variantId', // ✅ Pass variant ID
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
        $variantId = $request->get('variant_id'); // ✅ Added variant filter
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
        
        // ✅ Apply variant filter
        if ($variantId) {
            $query->whereHas('inventoryItems', function($q) use ($variantId) {
                $q->where('variant_id', $variantId);
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
            $currentStock = $inventoryItem ? $inventoryItem->quantity_allocated : 0;
            
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
        
        // ✅ Get variants for filter dropdown
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get();
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.movement-analysis', compact(
            'movementData',
            'movementStats',
            'sortedMovement',
            'departments',
            'locations',
            'variants', // ✅ Pass variants to view
            'startDate',
            'endDate',
            'movementType',
            'departmentId',
            'locationId',
            'variantId', // ✅ Pass variant ID
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
        $variantId = $request->get('variant_id'); // ✅ Added variant filter
        $valuationMethod = $request->get('valuation_method', 'cost');
        $perPage = (int)$request->get('per_page', 15);
        
        // Build query for inventory items
        $query = InventoryItems::with([
            'variant.product',
            'departmentItem',
            'itemLocation'
        ])
        ->where('tenant_id', $tenantId)
        ->where('quantity_allocated', '>', 0); // ✅ Use quantity_allocated
        
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        // ✅ Apply variant filter
        if ($variantId) {
            $query->where('variant_id', $variantId);
        }
        
        // Get ALL inventory items for summary calculations (unpaginated)
        $allItems = $query->get();
        
        // Calculate valuation metrics for ALL items using collections
        $allItemsWithValuation = $allItems->map(function ($item) use ($valuationMethod) {
            // ✅ Use grand_total_cost_price as the actual cost
            $costPrice = $item->variant->grand_total_cost_price ?? 0;
            $sellingPrice = $item->variant->selling_price ?? 0;
            $quantity = $item->quantity_allocated;
            
            // Calculate item value based on valuation method
            $itemValue = $quantity * $costPrice;
            $potentialProfit = $quantity * ($sellingPrice - $costPrice);
            $profitMargin = $costPrice > 0 ? (($sellingPrice - $costPrice) / $costPrice) * 100 : 0;
            
            $item->valuation_value = $itemValue;
            $item->potential_profit = $potentialProfit;
            $item->profit_margin = $profitMargin;
            $item->cost_price = $costPrice;
            $item->selling_price = $sellingPrice;
            
            return $item;
        });
        
        // Calculate valuation summary from ALL items
        $totalValue = $allItemsWithValuation->sum('valuation_value');
        $totalQuantity = $allItemsWithValuation->sum('quantity_allocated');
        $totalProfit = $allItemsWithValuation->sum('potential_profit');
        
        $valuationSummary = [
            'total_items' => $allItemsWithValuation->count(),
            'total_quantity' => $totalQuantity,
            'total_value' => $totalValue,
            'avg_unit_cost' => $totalQuantity > 0 ? $totalValue / $totalQuantity : 0,
            'avg_unit_price' => $allItemsWithValuation->avg(function($item) {
                return $item->variant->selling_price ?? 0;
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
        
        // ✅ Get variants for filter dropdown
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get();
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.valuation', compact(
            'inventoryItems',
            'valuationSummary',
            'valueByDepartment',
            'valueByLocation',
            'departments',
            'locations',
            'variants', // ✅ Pass variants to view
            'departmentId',
            'locationId',
            'variantId', // ✅ Pass variant ID
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
        $variantId = $request->get('variant_id'); // ✅ Added variant filter
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
        ->where('quantity_allocated', '>', 0) // ✅ Use quantity_allocated
        ->when($departmentId, function($q, $departmentId) {
            return $q->where('department_id', $departmentId);
        })
        ->when($locationId, function($q, $locationId) {
            return $q->where('location_id', $locationId);
        })
        ->when($variantId, function($q, $variantId) { // ✅ Apply variant filter
            return $q->where('variant_id', $variantId);
        })
        ->get();
        
        // Get inventory transactions for movement data
        $transactions = InventoryTransactions::where('tenant_id', $tenantId)
            ->where('type', 'sale')
            ->where('quantity', '<', 0)
            ->get()
            ->groupBy(function($transaction) {
                return $transaction->inventoryItems->variant_id ?? null;
            });
        
        $deadStockCollection = collect();
        
        foreach ($allItems as $item) {
            $variantTransactions = $transactions->get($item->variant_id, collect());
            
            $lastMovementDate = $variantTransactions->isNotEmpty() 
                ? $variantTransactions->max('created_at') 
                : $item->created_at;
            
            $totalMovement = $variantTransactions->sum(function($transaction) {
                return abs($transaction->quantity);
            });
            
            $daysIdle = Carbon::parse($lastMovementDate)->diffInDays(now());
            $isExpired = $item->expiry_date && Carbon::parse($item->expiry_date)->lt(now());
            
            // ✅ Use grand_total_cost_price
            $costPrice = $item->variant->grand_total_cost_price ?? 0;
            $inventoryValue = $item->quantity_allocated * $costPrice;
            
            $isDeadStock = $daysIdle >= $daysThreshold;
            
            if ($includeExpired && $isExpired) {
                $isDeadStock = true;
            }
            
            if ($isDeadStock) {
                $deadStockCollection->push((object)[
                    'id' => $item->id,
                    'variant' => $item->variant,
                    'departmentItem' => $item->departmentItem,
                    'itemLocation' => $item->itemLocation,
                    'quantity_on_hand' => $item->quantity_allocated,
                    'expiry_date' => $item->expiry_date,
                    'last_movement_date' => $lastMovementDate,
                    'total_movement' => $totalMovement,
                    'days_idle' => $daysIdle,
                    'is_expired' => $isExpired,
                    'inventory_value' => $inventoryValue,
                    'cost_price' => $costPrice,
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
        
        $sortedDeadStock = $deadStockCollection->sortByDesc('days_idle')->values();
        
        $summary = [
            'total_items' => $sortedDeadStock->count(),
            'total_quantity' => $sortedDeadStock->sum('quantity_on_hand'),
            'total_value' => $sortedDeadStock->sum('inventory_value'),
            'avg_days_idle' => $sortedDeadStock->avg('days_idle') ?? 0,
            'expired_items' => $sortedDeadStock->where('is_expired', true)->count(),
        ];
        
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
        
        $departmentDeadStock = $sortedDeadStock->groupBy(function($item) {
            return $item->department_name;
        })->map(function($items, $name) {
            return [
                'name' => $name,
                'quantity' => $items->sum('quantity_on_hand'),
                'value' => $items->sum('inventory_value')
            ];
        })->sortByDesc('quantity')->values();
        
        $deadStockItems = $this->paginateCollection($sortedDeadStock, $perPage, 'page');
        
        // ✅ Get variants for filter dropdown
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get();
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.dead-stock', compact(
            'deadStockItems',
            'summary',
            'idleCategories',
            'departmentDeadStock',
            'departments',
            'locations',
            'variants', // ✅ Pass variants
            'departmentId',
            'locationId',
            'variantId', // ✅ Pass variant ID
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
        $variantId = $request->get('variant_id'); // ✅ Added variant filter
        $excessThreshold = (float)$request->get('excess_threshold', 1.5);
        $perPage = (int)$request->get('per_page', 15);
        
        // Get all inventory items with preferred stock level
        $allItems = InventoryItems::with([
            'variant.product',
            'departmentItem',
            'itemLocation'
        ])
        ->where('tenant_id', $tenantId)
        ->where('quantity_allocated', '>', 0) // ✅ Use quantity_allocated
        ->where('preferred_stock_level', '>', 0)
        ->when($departmentId, function($q, $departmentId) {
            return $q->where('department_id', $departmentId);
        })
        ->when($locationId, function($q, $locationId) {
            return $q->where('location_id', $locationId);
        })
        ->when($variantId, function($q, $variantId) { // ✅ Apply variant filter
            return $q->where('variant_id', $variantId);
        })
        ->get();
        
        // Calculate excess stock metrics using collections
        $excessCollection = collect();
        
        foreach ($allItems as $item) {
            $currentStock = $item->quantity_allocated;
            $preferredStock = $item->preferred_stock_level;
            $thresholdStock = $preferredStock * $excessThreshold;
            $excessQuantity = max(0, $currentStock - $thresholdStock);
            
            if ($excessQuantity > 0) {
                $excessPercentage = (($currentStock / $preferredStock) - 1) * 100;
                // ✅ Use grand_total_cost_price
                $costPrice = $item->variant->grand_total_cost_price ?? 0;
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
        
        // ✅ Get variants for filter dropdown
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get();
        
        $departments = Department::where('tenant_id', $tenantId)->get();
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.inventory.excess-stock', compact(
            'excessStockItems',
            'summary',
            'excessCategories',
            'departments',
            'locations',
            'variants', // ✅ Pass variants
            'departmentId',
            'locationId',
            'variantId', // ✅ Pass variant ID
            'excessThreshold',
            'perPage'
        ));
    }
    
}