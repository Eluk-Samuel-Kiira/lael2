<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptItem;
use App\Models\ReceivedProductVariant;
use App\Models\ProductVariant;
use App\Models\Location;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class PurchasingReportsController extends Controller
{
    /**
     * Get current tenant ID
     */
    private function getTenantId()
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('purchasing reports')) {
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
     * Purchase Order Summary Report with Pure Eloquent
     */
    public function purchaseOrderSummary(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        // Filter parameters
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        $status = $request->get('status', 'all');
        $locationId = $request->get('location_id');
        $perPage = (int)$request->get('per_page', 15);
        
        // Get ALL purchase orders for calculations (unpaginated)
        $query = PurchaseOrder::with(['supplier', 'location', 'creator'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Apply filters to the query
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }
        
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        // Get ALL orders for summary calculations
        $allOrders = $query->get();
        
        // Calculate summary statistics from ALL orders
        $summary = [
            'total_orders' => $allOrders->count(),
            'total_value' => $allOrders->sum('total'),
            'average_order_value' => $allOrders->avg('total') ?? 0,
            'pending_orders' => $allOrders->whereIn('status', ['draft', 'sent', 'pending_approval'])->count(),
            'completed_orders' => $allOrders->whereIn('status', ['received', 'partially_received'])->count(),
            'cancelled_orders' => $allOrders->where('status', 'cancelled')->count(),
        ];
        
        // Apply pagination to the collection
        $sortedOrders = $allOrders->sortByDesc('created_at')->values();
        $purchaseOrders = $this->paginateCollection($sortedOrders, $perPage, 'page');
        
        // Get data for filter dropdowns
        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.purchasing.purchase-order-summary', compact(
            'purchaseOrders',
            'summary',
            'suppliers',
            'locations',
            'startDate',
            'endDate',
            'supplierId',
            'status',
            'locationId',
            'perPage'
        ));
    }

    /**
     * Supplier Performance Report - With Pagination
     */
    public function supplierPerformance(Request $request)
    { 
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        $perPage = (int)$request->get('per_page', 15);
        
        // Get all suppliers with filters
        $suppliersQuery = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true);
        
        if ($supplierId) {
            $suppliersQuery->where('id', $supplierId);
        }
        
        $allSuppliers = $suppliersQuery->get();
        
        // Calculate metrics for each supplier using collections
        $suppliersData = collect();
        
        foreach ($allSuppliers as $supplier) {
            // Get purchase orders for this period
            $purchaseOrders = PurchaseOrder::where('supplier_id', $supplier->id)
                ->where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->get();
            
            $total_orders = $purchaseOrders->count();
            $total_spent = $purchaseOrders->sum('total');
            $avg_order_value = $total_orders > 0 ? ($total_spent / $total_orders) : 0;
            
            // Get received orders for delivery metrics
            $receivedOrders = $purchaseOrders->where('status', 'received');
            $receivedCount = $receivedOrders->count();
            $on_time_orders = $receivedCount;
            
            // Calculate on-time delivery rate
            $on_time_delivery_rate = $total_orders > 0 
                ? ($on_time_orders / $total_orders) * 100 
                : 0;
            
            // Calculate average delivery days
            $avg_delivery_days = 0;
            if ($receivedCount > 0) {
                $totalDays = $receivedOrders->sum(function($order) {
                    if ($order->received_at && $order->created_at) {
                        return Carbon::parse($order->created_at)
                            ->diffInDays(Carbon::parse($order->received_at));
                    }
                    return 0;
                });
                $avg_delivery_days = $totalDays / $receivedCount;
            }
            
            // Calculate performance score
            $performanceScore = 0;
            if ($total_spent > 0) $performanceScore += 30;
            $performanceScore += ($on_time_delivery_rate * 0.4);
            $performanceScore += (max(0, 100 - ($avg_delivery_days * 2)) * 0.3);
            $performanceScore = min(100, $performanceScore);
            
            $suppliersData->push((object)[
                'id' => $supplier->id,
                'name' => $supplier->name,
                'contact_person' => $supplier->contact_person,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'total_orders' => $total_orders,
                'total_spent' => $total_spent,
                'avg_order_value' => $avg_order_value,
                'on_time_orders' => $on_time_orders,
                'on_time_delivery_rate' => $on_time_delivery_rate,
                'avg_delivery_days' => $avg_delivery_days,
                'performance_score' => $performanceScore,
                'spend_percentage' => 0,
                'classification' => 'C'
            ]);
        }
        
        // Calculate total spent for percentage calculation
        $totalSpentAllSuppliers = $suppliersData->sum('total_spent');
        
        // Calculate spend percentage and ABC classification
        $sortedSuppliers = $suppliersData->sortByDesc('total_spent')->values();
        $cumulativePercentage = 0;
        $processedSuppliers = [];
        
        foreach ($sortedSuppliers as $supplier) {
            if ($totalSpentAllSuppliers > 0) {
                $spendPercentage = ($supplier->total_spent / $totalSpentAllSuppliers) * 100;
                $supplier->spend_percentage = $spendPercentage;
                $cumulativePercentage += $spendPercentage;
                
                // ABC Classification
                if ($cumulativePercentage <= 80) {
                    $supplier->classification = 'A';
                } elseif ($cumulativePercentage <= 95) {
                    $supplier->classification = 'B';
                } else {
                    $supplier->classification = 'C';
                }
            } else {
                $supplier->spend_percentage = 0;
                $supplier->classification = 'C';
            }
            
            $processedSuppliers[] = $supplier;
        }
        
        // Apply pagination - THIS RETURNS A LengthAwarePaginator
        $allSuppliersCollection = collect($processedSuppliers);
        $paginatedSuppliers = $this->paginateCollection($allSuppliersCollection, $perPage, 'page');
        
        // Calculate chart data from ALL suppliers (not paginated) - for accurate charts
        $abcCounts = ['A' => 0, 'B' => 0, 'C' => 0];
        $deliveryRanges = ['excellent' => 0, 'good' => 0, 'fair' => 0, 'poor' => 0];
        $orderValueRanges = ['high' => 0, 'medium' => 0, 'low' => 0, 'very_low' => 0];
        
        foreach ($processedSuppliers as $supplier) {
            $abcCounts[$supplier->classification]++;
            
            $deliveryRate = $supplier->on_time_delivery_rate;
            if ($deliveryRate >= 90) {
                $deliveryRanges['excellent']++;
            } elseif ($deliveryRate >= 75) {
                $deliveryRanges['good']++;
            } elseif ($deliveryRate >= 60) {
                $deliveryRanges['fair']++;
            } else {
                $deliveryRanges['poor']++;
            }
            
            $avgValue = $supplier->avg_order_value;
            if ($avgValue >= 1000) {
                $orderValueRanges['high']++;
            } elseif ($avgValue >= 500) {
                $orderValueRanges['medium']++;
            } elseif ($avgValue >= 100) {
                $orderValueRanges['low']++;
            } else {
                $orderValueRanges['very_low']++;
            }
        }
        
        // Get top 10 suppliers for chart (from all suppliers)
        $topSuppliers = collect($processedSuppliers)
            ->sortByDesc('total_spent')
            ->take(10)
            ->values();
        
        $topSupplierNames = $topSuppliers->map(function($supplier) {
            $name = $supplier->name;
            return strlen($name) > 20 ? substr($name, 0, 20) . '...' : $name;
        })->toArray();
        
        $topSupplierSpends = $topSuppliers->pluck('total_spent')->toArray();
        
        // Overall summary
        $topSupplier = $processedSuppliers[0] ?? null;
        
        $summary = [
            'total_suppliers' => count($processedSuppliers),
            'total_spent' => $totalSpentAllSuppliers,
            'avg_order_value' => $totalSpentAllSuppliers > 0 ? ($totalSpentAllSuppliers / collect($processedSuppliers)->sum('total_orders')) : 0,
            'top_supplier' => $topSupplier,
        ];
        
        return view('reports.purchasing.supplier-performance', compact(
            'paginatedSuppliers',  // Changed from $suppliers to $paginatedSuppliers
            'summary',
            'abcCounts',
            'deliveryRanges',
            'orderValueRanges',
            'topSupplierNames',
            'topSupplierSpends',
            'startDate',
            'endDate',
            'supplierId',
            'perPage'
        ));
    }
                    

    /**
     * Purchase Order Status Report - Pure Eloquent
     */
    public function purchaseOrderStatus(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        $perPage = (int)$request->get('per_page', 15);
        
        // Get ALL purchase orders for status distribution (unpaginated)
        $statusQuery = PurchaseOrder::with(['supplier', 'location'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        if ($supplierId) {
            $statusQuery->where('supplier_id', $supplierId);
        }
        
        $allOrders = $statusQuery->get();
        
        // Calculate status distribution using collections
        $statusDistribution = $allOrders->groupBy('status')->map(function($orders, $status) {
            return (object)[
                'status' => $status,
                'count' => $orders->count(),
                'total_value' => $orders->sum('total'),
            ];
        })->keyBy('status');
        
        // Get pending orders (with pagination)
        $pendingQuery = PurchaseOrder::with(['supplier', 'location'])
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['draft', 'sent', 'pending_approval']);
        
        if ($supplierId) {
            $pendingQuery->where('supplier_id', $supplierId);
        }
        
        // Get ALL pending orders for summary total
        $allPending = $pendingQuery->get();
        $pendingTotalCount = $allPending->count();
        
        // Apply pagination to pending orders
        $pendingOrders = $this->paginateCollection($allPending, $perPage, 'pending_page');
        
        // Get overdue orders (with pagination)
        $overdueQuery = PurchaseOrder::with(['supplier', 'location'])
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['sent', 'approved'])
            ->whereNotNull('expected_delivery_date')
            ->where('expected_delivery_date', '<', now());
        
        if ($supplierId) {
            $overdueQuery->where('supplier_id', $supplierId);
        }
        
        // Get ALL overdue orders for summary total
        $allOverdue = $overdueQuery->get();
        $overdueTotalCount = $allOverdue->count();
        
        // Apply pagination to overdue orders
        $overdueOrders = $this->paginateCollection($allOverdue, $perPage, 'overdue_page');
        
        // Get suppliers for filter dropdown
        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // Calculate summary stats
        $completedCount = $statusDistribution->get('completed')?->count ?? 0;
        $completedValue = $statusDistribution->get('completed')?->total_value ?? 0;
        $totalOrders = $statusDistribution->sum('count');
        $totalValue = $statusDistribution->sum('total_value');
        
        return view('reports.purchasing.purchase-order-status', compact(
            'statusDistribution',
            'pendingOrders',
            'pendingTotalCount',
            'overdueOrders',
            'overdueTotalCount',
            'suppliers',
            'startDate',
            'endDate',
            'supplierId',
            'perPage',
            'totalOrders',
            'totalValue',
            'completedCount',
            'completedValue'
        ));
    }

    /**
     * Purchase Receipts Report - Pure Eloquent
     */
    public function purchaseReceipts(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        $locationId = $request->get('location_id');
        $perPage = (int)$request->get('per_page', 15);
        
        // Get ALL purchase receipts for summary calculations (unpaginated)
        $query = PurchaseReceipt::with([
            'purchaseOrder.supplier',
            'purchaseOrder.location',
            'receiver',
            'items.purchaseOrderItem'
        ])
        ->whereHas('purchaseOrder', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })
        ->whereBetween('received_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        if ($supplierId) {
            $query->whereHas('purchaseOrder', function ($q) use ($supplierId) {
                $q->where('supplier_id', $supplierId);
            });
        }
        
        if ($locationId) {
            $query->whereHas('purchaseOrder', function ($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        }
        
        // Get ALL receipts for summary calculations
        $allReceipts = $query->get();
        
        // Calculate summary statistics using collections
        $summary = [
            'total_receipts' => $allReceipts->count(),
            'total_quantity' => $allReceipts->sum(function($receipt) {
                return $receipt->items->sum('quantity_received');
            }),
            'unique_items' => $allReceipts->flatMap(function($receipt) {
                return $receipt->items->pluck('purchase_order_item_id');
            })->unique()->count(),
            'total_value' => $allReceipts->sum(function($receipt) {
                return $receipt->items->sum(function($item) {
                    return $item->quantity_received * ($item->unit_cost ?? $item->purchaseOrderItem->unit_cost ?? 0);
                });
            }),
        ];
        
        // Apply pagination to receipts
        $sortedReceipts = $allReceipts->sortByDesc('received_at')->values();
        $receipts = $this->paginateCollection($sortedReceipts, $perPage, 'page');
        
        // Add items_count and total_quantity to each receipt for display
        $receipts->each(function($receipt) {
            $receipt->items_count = $receipt->items->count();
            $receipt->total_quantity = $receipt->items->sum('quantity_received');
        });
        
        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $locations = Location::where('tenant_id', $tenantId)->get();
        
        return view('reports.purchasing.purchase-receipts', compact(
            'receipts',
            'summary',
            'suppliers',
            'locations',
            'startDate',
            'endDate',
            'supplierId',
            'locationId',
            'perPage'
        ));
    }

    /**
     * Supplier Spend Analysis Report - Pure Eloquent with Fixed Spend Trend
     */
    public function supplierSpendAnalysis(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(365)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        $period = $request->get('period', 'monthly');
        $perPage = (int)$request->get('per_page', 15);
        
        // Get ALL purchase orders for the period
        $query = PurchaseOrder::with(['supplier', 'location'])
            ->where('tenant_id', $tenantId)
            ->where('status', 'received')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }
        
        $allOrders = $query->get();
        
        // Group by supplier for supplier spend data
        $supplierSpendData = $allOrders->groupBy('supplier_id')->map(function($orders, $supplierId) {
            $supplier = $orders->first()->supplier;
            $totalSpent = $orders->sum('total');
            $orderCount = $orders->count();
            $avgOrderValue = $orderCount > 0 ? $totalSpent / $orderCount : 0;
            $minOrderValue = $orders->min('total');
            $maxOrderValue = $orders->max('total');
            $lastOrderDate = $orders->max('created_at');
            
            return (object)[
                'id' => $supplierId,
                'name' => $supplier ? $supplier->name : 'Unknown',
                'contact_person' => $supplier ? $supplier->contact_person : null,
                'total_spent' => $totalSpent,
                'order_count' => $orderCount,
                'avg_order_value' => $avgOrderValue,
                'min_order_value' => $minOrderValue,
                'max_order_value' => $maxOrderValue,
                'last_order_date' => $lastOrderDate,
                'supplier' => $supplier,
            ];
        })->sortByDesc('total_spent')->values();
        
        // Calculate summary statistics
        $totalSpentAll = $supplierSpendData->sum('total_spent');
        $totalOrdersAll = $supplierSpendData->sum('order_count');
        
        $summary = [
            'total_suppliers' => $supplierSpendData->count(),
            'total_spent' => $totalSpentAll,
            'total_orders' => $totalOrdersAll,
            'avg_order_value' => $totalOrdersAll > 0 ? $totalSpentAll / $totalOrdersAll : 0,
            'unique_suppliers' => $supplierSpendData->count(),
            'periods_analyzed' => 0,
        ];
        
        // Get top supplier
        $topSupplier = $supplierSpendData->first();
        
        // ✅ FIXED: Calculate spend trend by period for chart
        $spendTrend = collect();
        
        // Generate all periods between start and end dates
        $currentDate = Carbon::parse($startDate);
        $endDateObj = Carbon::parse($endDate);
        
        while ($currentDate <= $endDateObj) {
            $periodLabel = '';
            switch ($period) {
                case 'quarterly':
                    $quarter = ceil($currentDate->month / 3);
                    $periodLabel = $currentDate->year . '-Q' . $quarter;
                    $currentDate->addQuarter();
                    break;
                case 'yearly':
                    $periodLabel = (string)$currentDate->year;
                    $currentDate->addYear();
                    break;
                case 'monthly':
                default:
                    $periodLabel = $currentDate->format('Y-m');
                    $currentDate->addMonth();
                    break;
            }
            $spendTrend[$periodLabel] = 0;
        }
        
        // Fill in actual spend data
        foreach ($allOrders as $order) {
            $orderDate = Carbon::parse($order->created_at);
            switch ($period) {
                case 'quarterly':
                    $quarter = ceil($orderDate->month / 3);
                    $periodLabel = $orderDate->year . '-Q' . $quarter;
                    break;
                case 'yearly':
                    $periodLabel = (string)$orderDate->year;
                    break;
                default:
                    $periodLabel = $orderDate->format('Y-m');
                    break;
            }
            
            if ($spendTrend->has($periodLabel)) {
                $spendTrend[$periodLabel] += $order->total;
            } else {
                $spendTrend[$periodLabel] = $order->total;
            }
        }
        
        // Sort by period
        $spendTrend = $spendTrend->sortKeys();
        $summary['periods_analyzed'] = $spendTrend->count();
        
        // Apply pagination to supplier spend data
        $supplierSpend = $this->paginateCollection($supplierSpendData, $perPage, 'page');
        
        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // Debug: Log spend trend data
        \Log::info('Spend Trend Data:', [
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'trend_keys' => $spendTrend->keys(),
            'trend_values' => $spendTrend->values(),
            'has_data' => $spendTrend->count()
        ]);
        
        return view('reports.purchasing.supplier-spend-analysis', compact(
            'supplierSpend',
            'supplierSpendData',
            'summary',
            'spendTrend',
            'topSupplier',
            'suppliers',
            'startDate',
            'endDate',
            'supplierId',
            'period',
            'perPage'
        ));
    }

    /**
     * Purchase Order Items Analysis - Pure Eloquent
     */
    public function purchaseOrderItems(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        $productVariantId = $request->get('variant_id');
        $perPage = (int)$request->get('per_page', 15);
        
        // Get ALL purchase order items for summary calculations (unpaginated)
        $query = PurchaseOrderItem::with([
            'purchaseOrder.supplier',
            'purchaseOrder.location',
            'productVariant.product',
            'paymentMethod'
        ])
        ->whereHas('purchaseOrder', function ($q) use ($tenantId, $startDate, $endDate) {
            $q->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        });
        
        if ($supplierId) {
            $query->whereHas('purchaseOrder', function ($q) use ($supplierId) {
                $q->where('supplier_id', $supplierId);
            });
        }
        
        if ($productVariantId) {
            $query->where('product_variant_id', $productVariantId);
        }
        
        // Get ALL items for summary calculations
        $allItems = $query->get();
        
        // Calculate summary statistics from ALL items
        $summary = [
            'total_items' => $allItems->count(),
            'total_quantity' => $allItems->sum('quantity'),
            'total_value' => $allItems->sum('total_cost'),
            'avg_unit_cost' => $allItems->avg('unit_cost'),
            'unique_products' => $allItems->unique('product_variant_id')->count(),
            'unique_suppliers' => $allItems->unique(function ($item) {
                return $item->purchaseOrder->supplier_id ?? null;
            })->count(),
        ];
        
        // Top items by quantity from ALL items
        $topItemsByQuantity = $allItems->groupBy('product_variant_id')->map(function ($group) {
            $firstItem = $group->first();
            return [
                'product' => $firstItem->productVariant,
                'total_quantity' => $group->sum('quantity'),
                'total_value' => $group->sum('total_cost'),
                'order_count' => $group->count(),
            ];
        })->sortByDesc('total_quantity')->take(10);
        
        // Apply pagination to items
        $sortedItems = $allItems->sortByDesc('created_at')->values();
        $items = $this->paginateCollection($sortedItems, $perPage, 'page');
        
        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get();
        
        // Prepare chart data from top items
        $chartLabels = [];
        $chartQuantities = [];
        $chartValues = [];
        
        foreach ($topItemsByQuantity as $item) {
            $productName = $item['product'] ? ($item['product']->name ?? 'N/A') : 'N/A';
            if ($item['product'] && $item['product']->product) {
                $productName = $item['product']->product->name . ' - ' . $item['product']->name;
            }
            $chartLabels[] = strlen($productName) > 30 ? substr($productName, 0, 30) . '...' : $productName;
            $chartQuantities[] = $item['total_quantity'];
            $chartValues[] = $item['total_value'];
        }
        
        return view('reports.purchasing.purchase-order-items', compact(
            'items',
            'summary',
            'topItemsByQuantity',
            'suppliers',
            'variants',
            'startDate',
            'endDate',
            'supplierId',
            'productVariantId',
            'perPage',
            'chartLabels',
            'chartQuantities',
            'chartValues'
        ));
    }

    public function getSupplierSpendDetails(Request $request, $supplierId)
    {
        try {
            $tenantId = $this->getTenantId();
            $periodType = $request->get('period_type', 'monthly');
            $startDate = $request->get('start_date', now()->subDays(365)->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->format('Y-m-d'));
            
            // Get supplier details
            $supplier = Supplier::where('tenant_id', $tenantId)
                ->where('id', $supplierId)
                ->first();
            
            if (!$supplier) {
                return response()->json([
                    'success' => false,
                    'message' => 'Supplier not found'
                ], 404);
            }
            
            // Get purchase orders for this supplier within date range
            $query = PurchaseOrder::where('tenant_id', $tenantId)
                ->where('supplier_id', $supplierId)
                ->where('status', 'received')
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->with(['location', 'items.productVariant']);
            
            $purchaseOrders = $query->orderBy('created_at', 'desc')->get();
            
            // Calculate summary statistics
            $totalSpent = $purchaseOrders->sum('total');
            $orderCount = $purchaseOrders->count();
            $avgOrderValue = $orderCount > 0 ? $totalSpent / $orderCount : 0;
            $minOrderValue = $purchaseOrders->min('total') ?? 0;
            $maxOrderValue = $purchaseOrders->max('total') ?? 0;
            
            // Format purchase orders for display
            $formattedOrders = $purchaseOrders->map(function($order) {
                return [
                    'id' => $order->id,
                    'po_number' => $order->po_number,
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                    'status' => $order->status,
                    'total' => $order->total,
                    'location' => $order->location ? [
                        'id' => $order->location->id,
                        'name' => $order->location->name,
                    ] : null,
                    'notes' => $order->notes,
                    'items_count' => $order->items->count(),
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'supplier' => [
                        'id' => $supplier->id,
                        'name' => $supplier->name,
                        'contact_person' => $supplier->contact_person,
                        'email' => $supplier->email,
                        'phone' => $supplier->phone,
                        'address' => $supplier->address,
                    ],
                    'purchase_orders' => $formattedOrders,
                    'total_spent' => $totalSpent,
                    'order_count' => $orderCount,
                    'avg_order_value' => $avgOrderValue,
                    'min_order_value' => $minOrderValue,
                    'max_order_value' => $maxOrderValue,
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching supplier spend details: ' . $e->getMessage(), [
                'supplier_id' => $supplierId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error loading supplier details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Payment Status Report - Pure Eloquent
     */
    public function paymentStatus(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $paymentStatus = $request->get('payment_status', 'all');
        $supplierId = $request->get('supplier_id');
        $perPage = (int)$request->get('per_page', 15);
        
        // Get ALL purchase order items for summary calculations (unpaginated)
        $query = PurchaseOrderItem::with([
            'purchaseOrder.supplier',
            'purchaseOrder.location',
            'paymentMethod',
            'productVariant.product'
        ])
        ->whereHas('purchaseOrder', function ($q) use ($tenantId, $startDate, $endDate) {
            $q->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        });
        
        if ($paymentStatus !== 'all') {
            $query->where('payment_status', $paymentStatus);
        }
        
        if ($supplierId) {
            $query->whereHas('purchaseOrder', function ($q) use ($supplierId) {
                $q->where('supplier_id', $supplierId);
            });
        }
        
        // Get ALL items for summary calculations
        $allItems = $query->get();
        
        // Calculate payment status summary using collections
        $statusSummary = $allItems->groupBy('payment_status')->map(function($items, $status) {
            return (object)[
                'payment_status' => $status,
                'count' => $items->count(),
                'total_amount' => $items->sum('total_cost'),
            ];
        })->keyBy('payment_status');
        
        // Get overdue payments (payment_date passed and not fully paid)
        $overduePayments = $allItems->filter(function($item) {
            return $item->payment_status !== 'paid' 
                && $item->payment_date 
                && Carbon::parse($item->payment_date)->lt(now());
        })->sortBy('payment_date')->values();
        
        // Calculate summary statistics
        $totalAmountDue = $allItems->where('payment_status', '!=', 'paid')->sum('total_cost');
        $totalAmountPaid = $allItems->where('payment_status', 'paid')->sum('total_cost');
        $overdueAmount = $overduePayments->sum('total_cost');
        $overdueCount = $overduePayments->count();
        
        $summary = [
            'total_amount_due' => $totalAmountDue,
            'total_amount_paid' => $totalAmountPaid,
            'overdue_amount' => $overdueAmount,
            'overdue_count' => $overdueCount,
            'total_items' => $allItems->count(),
        ];
        
        // Apply pagination to items
        $sortedItems = $allItems->sortBy('payment_date')->values();
        $items = $this->paginateCollection($sortedItems, $perPage, 'page');
        
        // Get data for filter dropdowns
        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)->get();
        
        // Prepare chart data
        $statusLabels = [];
        $statusData = [];
        $statusColors = [];
        
        $statusConfig = [
            'pending' => ['label' => __('passwords.pending'), 'color' => '#FFC700'],
            'partial' => ['label' => __('passwords.partial'), 'color' => '#17a2b8'],
            'paid' => ['label' => __('passwords.paid'), 'color' => '#50CD89'],
            'overdue' => ['label' => __('passwords.overdue'), 'color' => '#F1416C'],
            'cancelled' => ['label' => __('passwords.cancelled'), 'color' => '#6c757d']
        ];
        
        foreach ($statusConfig as $status => $config) {
            $statusItem = $statusSummary->get($status);
            if ($statusItem || $status == 'overdue') {
                $statusLabels[] = $config['label'];
                $amount = $status == 'overdue' ? $overdueAmount : ($statusItem ? $statusItem->total_amount : 0);
                if ($amount > 0 || $status == 'overdue') {
                    $statusData[] = $amount;
                    $statusColors[] = $config['color'];
                }
            }
        }
        
        return view('reports.purchasing.payment-status', compact(
            'items',
            'statusSummary',
            'overduePayments',
            'summary',
            'suppliers',
            'paymentMethods',
            'startDate',
            'endDate',
            'paymentStatus',
            'supplierId',
            'perPage',
            'statusLabels',
            'statusData',
            'statusColors'
        ));
    }

    /**
     * Received Inventory Report - Pure Eloquent
     */
    public function receivedInventory(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        $productVariantId = $request->get('variant_id');
        $batchNumber = $request->get('batch_number'); // NEW: Batch number filter
        $includeExpiring = $request->get('include_expiring', false);
        $perPage = (int)$request->get('per_page', 15);
        
        // Get ALL received product variants for summary calculations (unpaginated)
        $query = ReceivedProductVariant::with([
            'purchaseOrder.supplier',
            'purchaseOrder.location',
            'productVariant.product',
            'receiver'
        ])
        ->where('tenant_id', $tenantId)
        ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        if ($supplierId) {
            $query->whereHas('purchaseOrder', function ($q) use ($supplierId) {
                $q->where('supplier_id', $supplierId);
            });
        }
        
        if ($productVariantId) {
            $query->where('product_variant_id', $productVariantId);
        }
        
        // NEW: Filter by batch number
        if ($batchNumber) {
            $query->where('batch_number', 'LIKE', '%' . $batchNumber . '%');
        }
        
        if ($includeExpiring) {
            $query->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', now()->addDays(30));
        }
        
        // Get ALL items for summary calculations
        $allItems = $query->get();
        
        // Calculate summary statistics from ALL items
        $summary = [
            'total_items' => $allItems->count(),
            'total_quantity' => $allItems->sum('quantity_received'),
            'total_value' => $allItems->sum('total_cost'),
            'unique_products' => $allItems->unique('product_variant_id')->count(),
            'unique_suppliers' => $allItems->unique(function ($item) {
                return $item->purchaseOrder->supplier_id ?? null;
            })->count(),
            'expiring_soon' => $allItems->filter(function($item) {
                return $item->expiry_date && 
                    Carbon::parse($item->expiry_date)->lte(now()->addDays(30)) &&
                    Carbon::parse($item->expiry_date)->gt(now());
            })->count(),
        ];
        
        // Batch and expiry analysis from ALL items
        $batchAnalysis = $allItems->groupBy('batch_number')->map(function ($batchItems, $batchNumber) {
            $firstItem = $batchItems->first();
            $expiryDate = $firstItem->expiry_date ? Carbon::parse($firstItem->expiry_date) : null;
            $daysToExpiry = $expiryDate ? $expiryDate->diffInDays(now(), false) : null;
            
            return (object)[
                'batch_number' => $batchNumber ?: __('passwords.no_batch'),
                'total_quantity' => $batchItems->sum('quantity_received'),
                'total_value' => $batchItems->sum('total_cost'),
                'expiry_date' => $firstItem->expiry_date,
                'days_to_expiry' => $daysToExpiry,
            ];
        })->sortByDesc('total_quantity');
        
        // Apply pagination to received items
        $sortedItems = $allItems->sortByDesc('created_at')->values();
        $receivedItems = $this->paginateCollection($sortedItems, $perPage, 'page');
        
        // NEW: Get unique batch numbers for the filter dropdown
        $batchNumbers = ReceivedProductVariant::where('tenant_id', $tenantId)
            ->whereNotNull('batch_number')
            ->where('batch_number', '!=', '')
            ->select('batch_number')
            ->distinct()
            ->orderBy('batch_number')
            ->pluck('batch_number');
        
        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get();
        
        return view('reports.purchasing.received-inventory', compact(
            'receivedItems',
            'summary',
            'batchAnalysis',
            'suppliers',
            'variants',
            'batchNumbers', // NEW: Pass batch numbers to view
            'startDate',
            'endDate',
            'supplierId',
            'productVariantId',
            'batchNumber', // NEW: Pass selected batch number
            'includeExpiring',
            'perPage'
        ));
    }


    /**
     * Supplier Risk Assessment Report - Pure Eloquent with Pagination
     */
    public function supplierRiskAssessment(Request $request)
    {
        $tenantId = $this->getTenantId();
        $perPage = (int)$request->get('per_page', 15);
        $criticalPerPage = (int)$request->get('critical_per_page', 10);
        
        // Get all active suppliers with risk metrics using pure Eloquent
        $allSuppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->withCount(['purchaseOrders as total_orders'])
            ->get();
        
        $suppliersWithRisk = collect();
        
        foreach ($allSuppliers as $supplier) {
            // Get purchase orders for delivery stats
            $receivedOrders = PurchaseOrder::where('supplier_id', $supplier->id)
                ->where('tenant_id', $tenantId)
                ->where('status', 'received')
                ->get();
            
            // Calculate delivery statistics using collections
            $deliveredOrders = $receivedOrders->count();
            $avgDeliveryDays = 0;
            
            if ($deliveredOrders > 0) {
                $totalDeliveryDays = $receivedOrders->sum(function($order) {
                    if ($order->received_at && $order->created_at) {
                        return Carbon::parse($order->created_at)->diffInDays(Carbon::parse($order->received_at));
                    }
                    return 0;
                });
                $avgDeliveryDays = $totalDeliveryDays / $deliveredOrders;
            }
            
            // Get last 10 orders for order frequency
            $recentOrders = PurchaseOrder::where('supplier_id', $supplier->id)
                ->where('tenant_id', $tenantId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            
            // Calculate order frequency
            $orderFrequency = $supplier->total_orders > 0 ? $recentOrders->count() / 30 : 0;
            
            // Calculate total spent
            $totalSpent = PurchaseOrder::where('supplier_id', $supplier->id)
                ->where('tenant_id', $tenantId)
                ->sum('total');
            
            // Calculate risk score factors
            $riskScore = 0;
            $riskFactors = [];
            
            // Factor 1: Order frequency
            $orderFrequencyRisk = max(0, 30 - ($orderFrequency * 10));
            $riskScore += $orderFrequencyRisk;
            if ($orderFrequencyRisk > 15) $riskFactors[] = 'low_order_frequency';
            
            // Factor 2: Payment terms
            $paymentTerms = $supplier->payment_terms ?? 30;
            $paymentTermRisk = $paymentTerms > 60 ? 30 : ($paymentTerms > 45 ? 25 : ($paymentTerms > 30 ? 20 : 10));
            $riskScore += $paymentTermRisk;
            if ($paymentTerms > 45) $riskFactors[] = 'extended_payment_terms';
            
            // Factor 3: Single sourcing risk
            $isCritical = $totalSpent > 10000;
            $sourcingRisk = $isCritical ? 25 : 5;
            $riskScore += $sourcingRisk;
            if ($isCritical) $riskFactors[] = 'single_sourcing_risk';
            
            // Factor 4: Delivery performance
            $deliveryRisk = $avgDeliveryDays > 21 ? 25 : ($avgDeliveryDays > 14 ? 20 : ($avgDeliveryDays > 7 ? 10 : 0));
            $riskScore += $deliveryRisk;
            if ($avgDeliveryDays > 14) $riskFactors[] = 'slow_delivery';
            
            // Factor 5: Geographic risk
            $geographicRisk = ($supplier->country_code && !in_array(strtoupper($supplier->country_code), ['US', 'CA', 'MX'])) ? 15 : 0;
            $riskScore += $geographicRisk;
            if ($geographicRisk > 0) $riskFactors[] = 'international_supplier';
            
            // Determine risk level
            if ($riskScore >= 70) {
                $riskLevel = 'high';
            } elseif ($riskScore >= 40) {
                $riskLevel = 'medium';
            } else {
                $riskLevel = 'low';
            }
            
            // Last order date
            $lastOrder = $recentOrders->first();
            $daysSinceLastOrder = $lastOrder ? Carbon::parse($lastOrder->created_at)->diffInDays(now()) : null;
            
            $suppliersWithRisk->push((object)[
                'id' => $supplier->id,
                'name' => $supplier->name,
                'contact_person' => $supplier->contact_person,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'country_code' => $supplier->country_code,
                'payment_terms_days' => $paymentTerms,
                'total_orders' => $supplier->total_orders,
                'total_spent' => $totalSpent,
                'risk_score' => min(100, round($riskScore, 1)),
                'risk_level' => $riskLevel,
                'risk_factors' => $riskFactors,
                'avg_delivery_days' => round($avgDeliveryDays, 1),
                'delivered_orders' => $deliveredOrders,
                'order_frequency' => round($orderFrequency, 2),
                'last_order_date' => $lastOrder ? $lastOrder->created_at : null,
                'days_since_last_order' => $daysSinceLastOrder,
            ]);
        }
        
        // Sort by risk score
        $sortedSuppliers = $suppliersWithRisk->sortByDesc('risk_score')->values();
        
        // Risk distribution
        $riskDistribution = [
            'high' => $sortedSuppliers->where('risk_level', 'high')->count(),
            'medium' => $sortedSuppliers->where('risk_level', 'medium')->count(),
            'low' => $sortedSuppliers->where('risk_level', 'low')->count(),
        ];
        
        // Critical suppliers (high risk and high spend) - Get ALL first
        $allCriticalSuppliers = $sortedSuppliers->filter(function($supplier) {
            return $supplier->risk_level === 'high' && $supplier->total_spent > 5000;
        })->values();
        
        // Apply pagination to critical suppliers
        $criticalSuppliers = $this->paginateCollection($allCriticalSuppliers, $criticalPerPage, 'critical_page');
        
        // Risk score statistics
        $riskStats = [
            'avg_risk_score' => $sortedSuppliers->avg('risk_score') ?? 0,
            'max_risk_score' => $sortedSuppliers->max('risk_score') ?? 0,
            'min_risk_score' => $sortedSuppliers->min('risk_score') ?? 0,
        ];
        
        // Apply pagination to main suppliers
        $suppliers = $this->paginateCollection($sortedSuppliers, $perPage, 'page');
        
        // Prepare chart data
        $riskChartData = [
            $riskDistribution['high'],
            $riskDistribution['medium'],
            $riskDistribution['low']
        ];
        $riskLabels = ['High Risk', 'Medium Risk', 'Low Risk'];
        $riskColors = ['#F1416C', '#FFC700', '#50CD89'];
        
        return view('reports.purchasing.supplier-risk-assessment', compact(
            'suppliers',
            'sortedSuppliers',
            'riskDistribution',
            'criticalSuppliers',
            'allCriticalSuppliers',
            'riskStats',
            'riskChartData',
            'riskLabels',
            'riskColors',
            'perPage',
            'criticalPerPage'
        ));
    }


    /**
     * Purchase Cost Analysis Report - Pure Eloquent
     */
    public function purchaseCostAnalysis(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(180)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $productVariantId = $request->get('variant_id');
        $supplierId = $request->get('supplier_id');
        $perPage = (int)$request->get('per_page', 15);
        
        // Get ALL received product variants for the period
        $query = ReceivedProductVariant::with([
            'productVariant.product.category',
            'purchaseOrder.supplier'
        ])
        ->where('tenant_id', $tenantId)
        ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        if ($productVariantId) {
            $query->where('product_variant_id', $productVariantId);
        }
        
        if ($supplierId) {
            $query->whereHas('purchaseOrder', function ($q) use ($supplierId) {
                $q->where('supplier_id', $supplierId);
            });
        }
        
        $allReceivedItems = $query->get();
        
        // Group by product variant for product analysis
        $productAnalysisCollection = $allReceivedItems->groupBy('product_variant_id')->map(function ($items, $variantId) {
            $firstItem = $items->first();
            $product = $firstItem->productVariant;
            
            // Sort by purchase date for price change calculation
            $sortedByDate = $items->sortBy('created_at');
            $oldestPrice = $sortedByDate->first()->unit_cost ?? 0;
            $newestPrice = $sortedByDate->last()->unit_cost ?? 0;
            
            $totalQuantity = $items->sum('quantity_received');
            $totalCost = $items->sum('total_cost');
            $avgUnitCost = $totalQuantity > 0 ? $totalCost / $totalQuantity : 0;
            $priceChange = $newestPrice - $oldestPrice;
            $priceChangePercentage = $oldestPrice > 0 ? ($priceChange / $oldestPrice) * 100 : 0;
            $purchaseCount = $items->count();
            
            return (object)[
                'product' => $product,
                'variant_id' => $variantId,
                'total_quantity' => $totalQuantity,
                'total_cost' => $totalCost,
                'avg_unit_cost' => $avgUnitCost,
                'oldest_price' => $oldestPrice,
                'newest_price' => $newestPrice,
                'price_change' => $priceChange,
                'price_change_percentage' => $priceChangePercentage,
                'purchase_count' => $purchaseCount,
            ];
        })->sortByDesc('total_cost');
        
        // Calculate summary statistics
        $summary = [
            'total_products' => $productAnalysisCollection->count(),
            'total_quantity' => $productAnalysisCollection->sum('total_quantity'),
            'total_cost' => $productAnalysisCollection->sum('total_cost'),
            'avg_price_increase' => $productAnalysisCollection->avg('price_change_percentage') ?? 0,
            'products_with_price_increase' => $productAnalysisCollection->filter(function($item) {
                return $item->price_change_percentage > 0;
            })->count(),
            'products_with_price_decrease' => $productAnalysisCollection->filter(function($item) {
                return $item->price_change_percentage < 0;
            })->count(),
        ];
        
        // Price trend analysis by date
        $priceTrends = $allReceivedItems->groupBy(function($item) {
            return $item->created_at->format('Y-m-d');
        })->map(function ($items, $date) {
            $totalQuantity = $items->sum('quantity_received');
            $totalCost = $items->sum('total_cost');
            $avgUnitCost = $totalQuantity > 0 ? $totalCost / $totalQuantity : 0;
            
            return (object)[
                'date' => $date,
                'avg_unit_cost' => $avgUnitCost,
                'total_quantity' => $totalQuantity,
                'total_cost' => $totalCost,
            ];
        })->sortKeys();
        
        // Top 10 products by spend (from full dataset)
        $topProducts = $productAnalysisCollection->take(10);
        
        // Apply pagination to the product analysis table
        $productAnalysis = $this->paginateCollection($productAnalysisCollection, $perPage, 'page');
        
        // Prepare chart data
        $chartDates = $priceTrends->pluck('date')->toArray();
        $chartPrices = $priceTrends->pluck('avg_unit_cost')->toArray();
        $chartQuantities = $priceTrends->pluck('total_quantity')->toArray();
        
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get();
        
        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('reports.purchasing.purchase-cost-analysis', compact(
            'productAnalysis',
            'productAnalysisCollection',
            'priceTrends',
            'summary',
            'topProducts',
            'variants',
            'suppliers',
            'chartDates',
            'chartPrices',
            'chartQuantities',
            'startDate',
            'endDate',
            'productVariantId',
            'supplierId',
            'perPage'
        ));
    }

    /**
     * Helper Methods
     */
    private function calculateReceiptsTotalValue($receipts)
    {
        return $receipts->sum(function ($receipt) {
            return $receipt->items->sum(function ($item) {
                return $item->quantity_received * ($item->purchaseOrderItem->unit_cost ?? 0);
            });
        });
    }
    
    private function getPeriodSelect($period)
    {
        switch ($period) {
            case 'quarterly':
                return "CONCAT(YEAR(created_at), '-Q', QUARTER(created_at))";
            case 'yearly':
                return "YEAR(created_at)";
            case 'monthly':
            default:
                return "DATE_FORMAT(created_at, '%Y-%m')";
        }
    }
    
    /**
     * Export report data
     */
    public function export(Request $request)
    {
        $reportType = $request->get('report_type');
        $format = $request->get('format', 'csv');
        
        // Based on report type, gather data and export
        // This is a simplified version - implement based on your export requirements
        
        return response()->json([
            'success' => true,
            'message' => 'Export functionality to be implemented',
        ]);
    }
}