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

class PurchasingReportsController extends Controller
{
    /**
     * Get current tenant ID
     */
    private function getTenantId()
    {
        return auth()->user()->tenant_id;
    }

    /**
     * Purchase Order Summary Report
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
        
        // Query purchase orders
        $query = PurchaseOrder::with(['supplier', 'location', 'creator'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Apply filters
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }
        
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        $purchaseOrders = $query->orderBy('created_at', 'desc')->paginate(25);
        
        // Summary statistics
        $summary = [
            'total_orders' => $query->count(),
            'total_value' => $query->sum('total'),
            'average_order_value' => $query->avg('total'),
            'pending_orders' => $query->clone()->whereIn('status', ['draft', 'sent', 'pending_approval'])->count(),
            'completed_orders' => $query->clone()->whereIn('status', ['received', 'partially_received'])->count(),
            'cancelled_orders' => $query->clone()->where('status', 'cancelled')->count(),
        ];
        
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
            'locationId'
        ));
    }

    /**
     * Supplier Performance Report - NO PAGINATION VERSION
     */
    public function supplierPerformance(Request $request)
    { 
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        
        // Get all suppliers
        $query = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true);
        
        if ($supplierId) {
            $query->where('id', $supplierId);
        }
        
        $allSuppliers = $query->get();
        
        // Calculate metrics for each supplier
        $suppliersData = collect();
        
        foreach ($allSuppliers as $supplier) {
            // Load purchase orders for this period
            $purchaseOrders = PurchaseOrder::where('supplier_id', $supplier->id)
                ->where('tenant_id', $this->getTenantId())
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->get();
            
            $total_orders = $purchaseOrders->count();
            $total_spent = $purchaseOrders->sum('total');
            $avg_order_value = $total_orders > 0 ? ($total_spent / $total_orders) : 0;
            
            // Get received orders for delivery metrics
            $receivedOrders = $purchaseOrders->where('status', 'received');
            $on_time_orders = $receivedOrders->count();
            
            // Calculate on-time delivery rate
            $on_time_delivery_rate = $total_orders > 0 
                ? ($on_time_orders / $total_orders) * 100 
                : 0;
            
            // Calculate average delivery days
            $avg_delivery_days = 0;
            if ($receivedOrders->count() > 0) {
                $totalDays = 0;
                foreach ($receivedOrders as $order) {
                    if ($order->received_at && $order->created_at) {
                        $totalDays += Carbon::parse($order->created_at)
                            ->diffInDays(Carbon::parse($order->received_at));
                    }
                }
                $avg_delivery_days = $totalDays / $receivedOrders->count();
            }
            
            $suppliersData->push([
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
                $spendPercentage = ($supplier['total_spent'] / $totalSpentAllSuppliers) * 100;
                $supplier['spend_percentage'] = $spendPercentage;
                $cumulativePercentage += $spendPercentage;
                
                // ABC Classification
                if ($cumulativePercentage <= 80) {
                    $supplier['classification'] = 'A';
                } elseif ($cumulativePercentage <= 95) {
                    $supplier['classification'] = 'B';
                } else {
                    $supplier['classification'] = 'C';
                }
            } else {
                $supplier['spend_percentage'] = 0;
                $supplier['classification'] = 'C';
            }
            
            $processedSuppliers[] = (object)$supplier;
        }
        
        // Convert to collection of objects for blade
        $suppliersCollection = collect($processedSuppliers);
        
        // Overall summary
        $topSupplier = $suppliersCollection->first();
        
        $summary = [
            'total_suppliers' => $allSuppliers->count(),
            'total_spent' => $totalSpentAllSuppliers,
            'avg_order_value' => $totalSpentAllSuppliers > 0 ? ($totalSpentAllSuppliers / $suppliersCollection->sum('total_orders')) : 0,
            'top_supplier' => $topSupplier,
        ];
        
        // \Log::info($suppliersCollection);
        
        return view('reports.purchasing.supplier-performance', [
            'suppliers' => $suppliersCollection,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'supplierId' => $supplierId
        ]);
    }
        

    /**
     * Purchase Order Status Report
     */
    public function purchaseOrderStatus(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        
        // Get status distribution
        $statusQuery = PurchaseOrder::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        if ($supplierId) {
            $statusQuery->where('supplier_id', $supplierId);
        }
        
        $statusDistribution = $statusQuery->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total_value'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');
        
        // Get pending orders
        $pendingQuery = PurchaseOrder::with(['supplier', 'location'])
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['draft', 'sent', 'pending_approval']);
        
        if ($supplierId) {
            $pendingQuery->where('supplier_id', $supplierId);
        }
        
        $pendingOrders = $pendingQuery->orderBy('expected_delivery_date', 'asc')->paginate(15);
        
        // Get overdue orders
        $overdueQuery = PurchaseOrder::with(['supplier', 'location'])
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['sent', 'approved'])
            ->whereNotNull('expected_delivery_date')
            ->where('expected_delivery_date', '<', now());
        
        if ($supplierId) {
            $overdueQuery->where('supplier_id', $supplierId);
        }
        
        $overdueOrders = $overdueQuery->orderBy('expected_delivery_date', 'asc')->paginate(15);
        
        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('reports.purchasing.purchase-order-status', compact(
            'statusDistribution',
            'pendingOrders',
            'overdueOrders',
            'suppliers',
            'startDate',
            'endDate',
            'supplierId'
        ));
    }

    /**
     * Purchase Receipts Report
     */
    public function purchaseReceipts(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        $locationId = $request->get('location_id');
        
        // Query purchase receipts
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
        
        $receipts = $query->orderBy('received_at', 'desc')->paginate(25);
        
        // Summary statistics
        $summaryQuery = PurchaseReceiptItem::whereHas('purchaseReceipt', function ($q) use ($tenantId, $startDate, $endDate) {
                $q->whereBetween('received_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                  ->whereHas('purchaseOrder', function ($q2) use ($tenantId) {
                      $q2->where('tenant_id', $tenantId);
                  });
            })
            ->select([
                DB::raw('COUNT(DISTINCT purchase_receipt_id) as total_receipts'),
                DB::raw('SUM(quantity_received) as total_quantity'),
                DB::raw('COUNT(DISTINCT purchase_order_item_id) as unique_items'),
            ])
            ->first();
        
        $summary = [
            'total_receipts' => $summaryQuery->total_receipts ?? 0,
            'total_quantity' => $summaryQuery->total_quantity ?? 0,
            'unique_items' => $summaryQuery->unique_items ?? 0,
            'total_value' => $this->calculateReceiptsTotalValue($receipts),
        ];
        
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
            'locationId'
        ));
    }

    /**
     * Supplier Spend Analysis Report
     */
    public function supplierSpendAnalysis(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(365)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $period = $request->get('period', 'monthly'); // monthly, quarterly, yearly
        
        // Get supplier spend data grouped by period
        $spendData = PurchaseOrder::where('tenant_id', $tenantId)
            ->where('status', 'received')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('
                supplier_id,
                ' . $this->getPeriodSelect($period) . ' as period,
                COUNT(*) as order_count,
                SUM(total) as total_spent,
                AVG(total) as avg_order_value,
                MIN(total) as min_order_value,
                MAX(total) as max_order_value
            ')
            ->groupBy('supplier_id', DB::raw($this->getPeriodSelect($period)))
            ->orderBy('period')
            ->orderBy('total_spent', 'desc')
            ->with('supplier')
            ->get();
        
        // Group by period for chart data
        $chartData = $spendData->groupBy('period')->map(function ($periodData, $period) {
            return [
                'period' => $period,
                'total_spent' => $periodData->sum('total_spent'),
                'order_count' => $periodData->sum('order_count'),
                'supplier_count' => $periodData->count(),
            ];
        })->sortKeys();
        
        // Top suppliers by total spend
        $topSuppliers = $spendData->groupBy('supplier_id')->map(function ($supplierData, $supplierId) {
            $supplier = $supplierData->first()->supplier;
            return [
                'supplier' => $supplier,
                'total_spent' => $supplierData->sum('total_spent'),
                'order_count' => $supplierData->sum('order_count'),
                'avg_order_value' => $supplierData->avg('total_spent'),
            ];
        })->sortByDesc('total_spent')->take(10);
        
        $summary = [
            'total_spent' => $spendData->sum('total_spent'),
            'total_orders' => $spendData->sum('order_count'),
            'unique_suppliers' => $spendData->unique('supplier_id')->count(),
            'avg_order_value' => $spendData->avg('total_spent'),
            'periods_analyzed' => $chartData->count(),
        ];
        
        return view('reports.purchasing.supplier-spend-analysis', compact(
            'spendData',
            'chartData',
            'topSuppliers',
            'summary',
            'startDate',
            'endDate',
            'period'
        ));
    }

    /**
     * Purchase Order Items Analysis
     */
    public function purchaseOrderItems(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        $productVariantId = $request->get('variant_id');
        
        // Query purchase order items
        $query = PurchaseOrderItem::with([
            'purchaseOrder.supplier',
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
        
        $items = $query->orderBy('created_at', 'desc')->paginate(25);
        
        // Summary statistics
        $summary = [
            'total_items' => $items->total(),
            'total_quantity' => $items->sum('quantity'),
            'total_value' => $items->sum('total_cost'),
            'avg_unit_cost' => $items->avg('unit_cost'),
            'unique_products' => $items->unique('product_variant_id')->count(),
            'unique_suppliers' => $items->unique(function ($item) {
                return $item->purchaseOrder->supplier_id;
            })->count(),
        ];
        
        // Top items by quantity
        $topItemsByQuantity = $items->groupBy('product_variant_id')->map(function ($group) {
            $firstItem = $group->first();
            return [
                'product' => $firstItem->productVariant,
                'total_quantity' => $group->sum('quantity'),
                'total_value' => $group->sum('total_cost'),
                'order_count' => $group->count(),
            ];
        })->sortByDesc('total_quantity')->take(10);
        
        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get();
        
        return view('reports.purchasing.purchase-order-items', compact(
            'items',
            'summary',
            'topItemsByQuantity',
            'suppliers',
            'variants',
            'startDate',
            'endDate',
            'supplierId',
            'productVariantId'
        ));
    }

    public function getSupplierSpendDetails(Request $request, $supplierId)
    {
        $tenantId = $this->getTenantId();
        $period = $request->get('period');
        $periodType = $request->get('period_type', 'monthly');
        
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
        
        // Build query based on period type
        $query = PurchaseOrder::where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->with('location');
        
        // Apply period filter
        if ($periodType === 'monthly') {
            $query->whereYear('created_at', substr($period, 0, 4))
                ->whereMonth('created_at', substr($period, 5));
        } elseif ($periodType === 'quarterly') {
            $year = substr($period, 0, 4);
            $quarter = substr($period, -1);
            $monthStart = ($quarter - 1) * 3 + 1;
            $monthEnd = $quarter * 3;
            
            $query->whereYear('created_at', $year)
                ->whereBetween(DB::raw('MONTH(created_at)'), [$monthStart, $monthEnd]);
        } elseif ($periodType === 'yearly') {
            $query->whereYear('created_at', $period);
        }
        
        $purchaseOrders = $query->get();
        
        // Calculate summary
        $summary = [
            'order_count' => $purchaseOrders->count(),
            'total_spent' => $purchaseOrders->sum('total'),
            'avg_order_value' => $purchaseOrders->avg('total'),
            'min_order_value' => $purchaseOrders->min('total'),
            'max_order_value' => $purchaseOrders->max('total'),
        ];
        
        return response()->json([
            'success' => true,
            'data' => [
                'supplier' => $supplier,
                'purchase_orders' => $purchaseOrders,
                ...$summary
            ]
        ]);
    }

    /**
     * Payment Status Report
     */
    public function paymentStatus(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $paymentStatus = $request->get('payment_status', 'all');
        $supplierId = $request->get('supplier_id');
        
        // Query purchase order items with payment status
        $query = PurchaseOrderItem::with([
            'purchaseOrder.supplier',
            'paymentMethod',
            'productVariant'
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
        
        $items = $query->orderBy('payment_date', 'asc')->paginate(25);
        
        // Payment status summary
        $statusSummary = PurchaseOrderItem::whereHas('purchaseOrder', function ($q) use ($tenantId, $startDate, $endDate) {
                $q->where('tenant_id', $tenantId)
                  ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            })
            ->select('payment_status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_cost) as total_amount'))
            ->groupBy('payment_status')
            ->get()
            ->keyBy('payment_status');
        
        // Overdue payments
        $overdueQuery = PurchaseOrderItem::with(['purchaseOrder.supplier', 'paymentMethod'])
            ->whereHas('purchaseOrder', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->where('payment_status', '!=', 'paid')
            ->whereNotNull('payment_date')
            ->where('payment_date', '<', now());
        
        if ($supplierId) {
            $overdueQuery->whereHas('purchaseOrder', function ($q) use ($supplierId) {
                $q->where('supplier_id', $supplierId);
            });
        }
        
        $overduePayments = $overdueQuery->orderBy('payment_date', 'asc')->get();
        
        $summary = [
            'total_amount_due' => $items->where('payment_status', '!=', 'paid')->sum('total_cost'),
            'total_amount_paid' => $items->where('payment_status', 'paid')->sum('total_cost'),
            'overdue_amount' => $overduePayments->sum('total_cost'),
            'overdue_count' => $overduePayments->count(),
        ];
        
        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $paymentMethods = PaymentMethod::where('tenant_id', $tenantId)->get();
        
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
            'supplierId'
        ));
    }

    /**
     * Received Inventory Report
     */
    public function receivedInventory(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        $productVariantId = $request->get('variant_id');
        $includeExpiring = $request->get('include_expiring', false);
        
        // Query received product variants
        $query = ReceivedProductVariant::with([
            'purchaseOrder.supplier',
            'productVariant.product',
            'receivedBy'
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
        
        if ($includeExpiring) {
            $query->whereNotNull('expiry_date')
                  ->where('expiry_date', '<=', now()->addDays(30));
        }
        
        $receivedItems = $query->orderBy('created_at', 'desc')->paginate(25);
        
        // Summary statistics
        $summary = [
            'total_items' => $receivedItems->total(),
            'total_quantity' => $receivedItems->sum('quantity_received'),
            'total_value' => $receivedItems->sum('total_cost'),
            'unique_products' => $receivedItems->unique('product_variant_id')->count(),
            'unique_suppliers' => $receivedItems->unique(function ($item) {
                return $item->purchaseOrder->supplier_id;
            })->count(),
            'expiring_soon' => $receivedItems->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', now()->addDays(30))
                ->where('expiry_date', '>', now())
                ->count(),
        ];
        
        // Batch and expiry analysis
        $batchAnalysis = $receivedItems->groupBy('batch_number')->map(function ($batchItems, $batchNumber) {
            return [
                'batch_number' => $batchNumber,
                'total_quantity' => $batchItems->sum('quantity_received'),
                'total_value' => $batchItems->sum('total_cost'),
                'expiry_date' => $batchItems->first()->expiry_date,
                'days_to_expiry' => $batchItems->first()->expiry_date 
                    ? Carbon::parse($batchItems->first()->expiry_date)->diffInDays(now()) 
                    : null,
            ];
        })->sortByDesc('total_quantity');
        
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
            'startDate',
            'endDate',
            'supplierId',
            'productVariantId',
            'includeExpiring'
        ));
    }

    /**
     * Supplier Risk Assessment Report
     */
    public function supplierRiskAssessment(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        // Get all active suppliers with risk metrics
        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->withCount(['purchaseOrders as total_orders'])
            ->withSum('purchaseOrders as total_spent', 'total') // Fixed: 2 arguments required
            ->with(['purchaseOrders' => function ($q) {
                $q->where('status', 'received')
                ->orderBy('received_at', 'desc')
                ->limit(10);
            }])
            ->get()
            ->map(function ($supplier) use ($tenantId) {
                // Calculate risk score based on multiple factors
                $riskScore = 0;
                $riskFactors = [];
                
                // Factor 1: Order frequency (lower frequency = higher risk)
                $orderFrequency = $supplier->total_orders > 0 
                    ? $supplier->purchaseOrders->count() / 30 // Assuming 30 days analysis
                    : 0;
                $orderFrequencyRisk = max(0, 30 - ($orderFrequency * 10));
                $riskScore += $orderFrequencyRisk;
                if ($orderFrequencyRisk > 15) {
                    $riskFactors[] = 'low_order_frequency';
                }
                
                // Factor 2: Payment terms (longer terms = higher risk)
                $paymentTerms = $supplier->payment_terms ?? 30; // Default 30 days
                $paymentTermRisk = $paymentTerms > 60 ? 30 : 
                                ($paymentTerms > 45 ? 25 : 
                                ($paymentTerms > 30 ? 20 : 10));
                $riskScore += $paymentTermRisk;
                if ($paymentTerms > 45) {
                    $riskFactors[] = 'extended_payment_terms';
                }
                
                // Factor 3: Single sourcing risk (if this supplier is critical)
                $totalSpent = $supplier->purchase_orders_sum_total ?? 0; // Changed from total_spent
                $isCritical = $totalSpent > 10000; // Example threshold
                $sourcingRisk = $isCritical ? 25 : 5;
                $riskScore += $sourcingRisk;
                if ($isCritical) {
                    $riskFactors[] = 'single_sourcing_risk';
                }
                
                // Factor 4: Delivery performance
                $deliveryStats = PurchaseOrder::where('supplier_id', $supplier->id)
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'received')
                    ->selectRaw('
                        AVG(DATEDIFF(received_at, created_at)) as avg_delivery_days,
                        COUNT(*) as delivered_orders
                    ')
                    ->first();
                
                $avgDeliveryDays = $deliveryStats->avg_delivery_days ?? 0;
                $deliveredOrders = $deliveryStats->delivered_orders ?? 0;
                
                $deliveryRisk = $avgDeliveryDays > 21 ? 25 :
                            ($avgDeliveryDays > 14 ? 20 :
                            ($avgDeliveryDays > 7 ? 10 : 0));
                $riskScore += $deliveryRisk;
                if ($avgDeliveryDays > 14) {
                    $riskFactors[] = 'slow_delivery';
                }
                
                // Factor 5: Geographic risk (if international)
                $geographicRisk = $supplier->country_code && !in_array(strtoupper($supplier->country_code), ['US', 'CA', 'MX']) ? 15 : 0;
                $riskScore += $geographicRisk;
                if ($geographicRisk > 0) {
                    $riskFactors[] = 'international_supplier';
                }
                
                // Factor 6: Quality issues (placeholder - would need actual data)
                $qualityRisk = 0; // Assume no quality issues by default
                $riskScore += $qualityRisk;
                
                // Factor 7: Contract expiration (placeholder)
                $contractExpirationRisk = 0; // Assume no immediate expiration
                $riskScore += $contractExpirationRisk;
                
                // Categorize risk level
                if ($riskScore >= 70) {
                    $riskLevel = 'high';
                } elseif ($riskScore >= 40) {
                    $riskLevel = 'medium';
                } else {
                    $riskLevel = 'low';
                }
                
                // Calculate additional metrics
                $supplier->risk_score = min(100, round($riskScore, 1));
                $supplier->risk_level = $riskLevel;
                $supplier->risk_factors = $riskFactors;
                $supplier->avg_delivery_days = round($avgDeliveryDays, 1);
                $supplier->delivered_orders = $deliveredOrders;
                $supplier->payment_terms_days = $paymentTerms;
                $supplier->order_frequency = round($orderFrequency, 2);
                $supplier->total_spent = $totalSpent; // Add this for easy access in blade
                
                // Calculate days since last order
                $lastOrder = $supplier->purchaseOrders->first();
                $supplier->last_order_date = $lastOrder ? $lastOrder->created_at : null;
                $supplier->days_since_last_order = $lastOrder ? $lastOrder->created_at->diffInDays(now()) : null;
                
                return $supplier;
            })
            ->sortByDesc('risk_score')
            ->values();
        
        // Risk distribution
        $riskDistribution = [
            'high' => $suppliers->where('risk_level', 'high')->count(),
            'medium' => $suppliers->where('risk_level', 'medium')->count(),
            'low' => $suppliers->where('risk_level', 'low')->count(),
        ];
        
        // Critical suppliers (high risk and high spend)
        $criticalSuppliers = $suppliers->where('risk_level', 'high')
            ->where('total_spent', '>', 5000)
            ->take(10)
            ->values();
        
        // Calculate risk score statistics
        $riskStats = [
            'avg_risk_score' => $suppliers->avg('risk_score') ?? 0,
            'max_risk_score' => $suppliers->max('risk_score') ?? 0,
            'min_risk_score' => $suppliers->min('risk_score') ?? 0,
        ];
        
        return view('reports.purchasing.supplier-risk-assessment', compact(
            'suppliers',
            'riskDistribution',
            'criticalSuppliers',
            'riskStats'
        ));
    }


    /**
     * Purchase Cost Analysis Report
     */
    public function purchaseCostAnalysis(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(180)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $productVariantId = $request->get('variant_id');
        $supplierId = $request->get('supplier_id');
        
        // Query purchase costs over time
        $query = ReceivedProductVariant::with([
            'productVariant.product',
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
        
        $costData = $query->select([
            'product_variant_id',
            DB::raw('DATE(created_at) as purchase_date'),
            DB::raw('AVG(unit_cost) as avg_unit_cost'),
            DB::raw('MIN(unit_cost) as min_unit_cost'),
            DB::raw('MAX(unit_cost) as max_unit_cost'),
            DB::raw('SUM(quantity_received) as total_quantity'),
            DB::raw('SUM(total_cost) as total_cost'),
        ])
        ->groupBy('product_variant_id', DB::raw('DATE(created_at)'))
        ->orderBy('purchase_date')
        ->get();
        
        // Group by product for analysis
        $productAnalysis = $costData->groupBy('product_variant_id')->map(function ($productData, $variantId) {
            $firstItem = $productData->first();
            $product = $firstItem->productVariant;
            $recentPrice = $productData->sortByDesc('purchase_date')->first()->avg_unit_cost;
            $oldestPrice = $productData->sortBy('purchase_date')->first()->avg_unit_cost;
            
            return [
                'product' => $product,
                'total_quantity' => $productData->sum('total_quantity'),
                'total_cost' => $productData->sum('total_cost'),
                'avg_unit_cost' => $productData->avg('avg_unit_cost'),
                'price_change' => $recentPrice - $oldestPrice,
                'price_change_percentage' => $oldestPrice > 0 ? (($recentPrice - $oldestPrice) / $oldestPrice) * 100 : 0,
                'purchase_count' => $productData->count(),
            ];
        })->sortByDesc('total_cost');
        
        // Price trend analysis
        $priceTrends = $costData->groupBy('purchase_date')->map(function ($dateData, $date) {
            return [
                'date' => $date,
                'avg_unit_cost' => $dateData->avg('avg_unit_cost'),
                'total_quantity' => $dateData->sum('total_quantity'),
                'total_cost' => $dateData->sum('total_cost'),
            ];
        })->sortKeys();
        
        $summary = [
            'total_products' => $productAnalysis->count(),
            'total_quantity' => $productAnalysis->sum('total_quantity'),
            'total_cost' => $productAnalysis->sum('total_cost'),
            'avg_price_increase' => $productAnalysis->avg('price_change_percentage'),
            'products_with_price_increase' => $productAnalysis->where('price_change_percentage', '>', 0)->count(),
            'products_with_price_decrease' => $productAnalysis->where('price_change_percentage', '<', 0)->count(),
        ];
        
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
            'priceTrends',
            'summary',
            'variants',
            'suppliers',
            'startDate',
            'endDate',
            'productVariantId',
            'supplierId'
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