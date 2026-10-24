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
        if (!tenant_can('purchasing_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
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

        $startDate  = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate    = $request->get('end_date',   now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        $status     = $request->get('status', 'all');
        $locationId = $request->get('location_id');
        $perPage    = (int) $request->get('per_page', 15);

        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $locations = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);

        return view('reports.purchasing.purchase-order-summary', compact(
            'suppliers', 'locations',
            'startDate', 'endDate', 'supplierId', 'status', 'locationId', 'perPage'
        ));
    }

    public function purchaseOrderSummaryData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('purchasing reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('purchasing_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate  = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate    = $request->get('end_date',   now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        $status     = $request->get('status', 'all');
        $locationId = $request->get('location_id');
        $perPage    = (int) $request->get('per_page', 15);
        $page       = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $query = PurchaseOrder::with(['supplier:id,name,contact_person', 'location:id,name'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        if ($supplierId) $query->where('supplier_id', $supplierId);
        if ($status !== 'all') $query->where('status', $status);
        if ($locationId) $query->where('location_id', $locationId);

        $allOrders = $query->get();

        $summary = [
            'total_orders'        => $allOrders->count(),
            'total_value'         => round($allOrders->sum('total'), 2),
            'average_order_value' => round($allOrders->avg('total') ?? 0, 2),
            'pending_orders'      => $allOrders->whereIn('status', ['draft', 'sent', 'pending_approval'])->count(),
            'completed_orders'    => $allOrders->whereIn('status', ['received', 'partially_received'])->count(),
            'cancelled_orders'    => $allOrders->where('status', 'cancelled')->count(),
        ];

        $total    = $allOrders->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;

        $rows = $allOrders->sortByDesc('created_at')->slice($offset, $perPage)->values()
            ->map(function ($order) {
                $overdue = $order->expected_delivery_date
                    && $order->expected_delivery_date->lt(now())
                    && !in_array($order->status, ['received', 'cancelled']);

                return (object) [
                    'id'                     => $order->id,
                    'po_number'              => $order->po_number,
                    'supplier_name'          => $order->supplier->name ?? '-',
                    'supplier_contact'       => $order->supplier->contact_person ?? null,
                    'location_name'          => $order->location->name ?? '-',
                    'created_at'             => $order->created_at?->format('Y-m-d'),
                    'expected_delivery_date' => $order->expected_delivery_date?->format('Y-m-d'),
                    'is_overdue'             => $overdue,
                    'status'                 => $order->status,
                    'status_label'           => __('passwords.' . $order->status),
                    'status_color'           => match ($order->status) {
                        'draft'              => 'secondary',
                        'sent'               => 'info',
                        'pending_approval'   => 'warning',
                        'approved'           => 'primary',
                        'partially_received' => 'success',
                        'received'           => 'success',
                        'cancelled'          => 'danger',
                        default              => 'dark',
                    },
                    'total'                  => round((float) $order->total, 2),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'summary'    => $summary,
                'items'      => $rows,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'from'         => $total > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $total),
                    'per_page'     => $perPage,
                ],
            ],
        ]);
    }

    public function purchaseOrderSummaryDetail($id)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('purchasing reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        $tenantId = $user->tenant_id;

        $order = PurchaseOrder::with([
                'supplier:id,name,contact_person,email,phone,address',
                'location:id,name',
                'creator:id,name',
                'items.productVariant:id,name,sku,product_id',
                'items.productVariant.product:id,name',
                'items.paymentMethod:id,name',
                'items' => function ($q) { $q->orderBy('id'); },
            ])
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->firstOrFail();

        $items = $order->items->map(function ($item) {
            $received = (float) ($item->received_quantity ?? 0);
            $ordered  = (float) ($item->quantity ?? 0);

            return [
                'id'                => $item->id,
                'product_name'      => $item->product_name,
                'sku'               => $item->sku,
                'variant_name'      => $item->productVariant?->name,
                'variant_product'   => $item->productVariant?->product?->name,
                'quantity'          => $ordered,
                'received_quantity' => $received,
                'unit_cost'         => round((float) ($item->unit_cost ?? 0), 2),
                'total_cost'        => round((float) ($item->total_cost ?? 0), 2),
                'payment_status'    => $item->payment_status,
                'payment_method'    => $item->paymentMethod?->name,
                'payment_date'      => $item->payment_date
                    ? Carbon::parse($item->payment_date)->format('Y-m-d')
                    : null,
                'is_fully_received' => $ordered > 0 && $received >= $ordered,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id'                     => $order->id,
                'po_number'              => $order->po_number,
                'status'                 => $order->status,
                'status_label'           => __('passwords.' . $order->status),
                'status_color'           => match ($order->status) {
                    'draft'              => 'secondary',
                    'sent'               => 'info',
                    'pending_approval'   => 'warning',
                    'approved'           => 'primary',
                    'partially_received' => 'success',
                    'received'           => 'success',
                    'cancelled'          => 'danger',
                    default              => 'dark',
                },
                'supplier' => [
                    'name'           => $order->supplier?->name ?? '-',
                    'contact_person' => $order->supplier?->contact_person,
                    'email'          => $order->supplier?->email,
                    'phone'          => $order->supplier?->phone,
                    'address'        => $order->supplier?->address,
                ],
                'location'               => $order->location?->name ?? '-',
                'created_by'             => $order->creator?->name ?? '-',
                'created_at'             => $order->created_at?->format('Y-m-d H:i'),
                'expected_delivery_date' => $order->expected_delivery_date?->format('Y-m-d'),
                'notes'                  => $order->notes,
                'subtotal'               => round((float) ($order->subtotal ?? 0), 2),
                'tax_total'              => round((float) ($order->tax_total ?? 0), 2),
                'total'                  => round((float) $order->total, 2),
                'items'                  => $items,
            ],
        ]);
    }

    public function supplierPerformance(Request $request)
    {
        $tenantId = $this->getTenantId();

        $startDate  = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate    = $request->get('end_date',   now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        $perPage    = (int) $request->get('per_page', 15);

        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('reports.purchasing.supplier-performance', compact(
            'suppliers',
            'startDate', 'endDate', 'supplierId', 'perPage'
        ));
    }

    public function supplierPerformanceData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('purchasing reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('purchasing_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate  = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate    = $request->get('end_date',   now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        $perPage    = (int) $request->get('per_page', 15);
        $page       = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $suppliersQuery = Supplier::where('tenant_id', $tenantId)->where('is_active', true);
        if ($supplierId) $suppliersQuery->where('id', $supplierId);
        $allSuppliers = $suppliersQuery->get();

        // ─── Per-supplier metrics ───────────────────────────────
        $suppliersData = collect();
        foreach ($allSuppliers as $supplier) {
            $purchaseOrders = PurchaseOrder::where('supplier_id', $supplier->id)
                ->where('tenant_id', $tenantId)
                ->whereBetween('created_at', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59',
                ])
                ->get();

            $totalOrders   = $purchaseOrders->count();
            $totalSpent    = (float) $purchaseOrders->sum('total');
            $avgOrderValue = $totalOrders > 0 ? $totalSpent / $totalOrders : 0;

            $receivedOrders = $purchaseOrders->where('status', 'received');
            $receivedCount  = $receivedOrders->count();
            $onTimeOrders   = $receivedCount;
            $onTimeRate     = $totalOrders > 0 ? ($onTimeOrders / $totalOrders) * 100 : 0;

            $avgDeliveryDays = 0;
            if ($receivedCount > 0) {
                $totalDays = $receivedOrders->sum(function ($order) {
                    if ($order->received_at && $order->created_at) {
                        return Carbon::parse($order->created_at)->diffInDays(Carbon::parse($order->received_at));
                    }
                    return 0;
                });
                $avgDeliveryDays = $totalDays / $receivedCount;
            }

            $perfScore = 0;
            if ($totalSpent > 0) $perfScore += 30;
            $perfScore += $onTimeRate * 0.4;
            $perfScore += max(0, 100 - ($avgDeliveryDays * 2)) * 0.3;
            $perfScore = min(100, $perfScore);

            $suppliersData->push((object) [
                'id'                    => $supplier->id,
                'name'                  => $supplier->name,
                'contact_person'        => $supplier->contact_person,
                'email'                 => $supplier->email,
                'phone'                 => $supplier->phone,
                'total_orders'          => $totalOrders,
                'total_spent'           => round($totalSpent, 2),
                'avg_order_value'       => round($avgOrderValue, 2),
                'on_time_orders'        => $onTimeOrders,
                'on_time_delivery_rate' => round($onTimeRate, 2),
                'avg_delivery_days'     => round($avgDeliveryDays, 2),
                'performance_score'     => round($perfScore, 2),
                'spend_percentage'      => 0,
                'classification'        => 'C',
            ]);
        }

        // ─── ABC classification ─────────────────────────────────
        $totalSpentAllSuppliers = (float) $suppliersData->sum('total_spent');
        $sortedSuppliers        = $suppliersData->sortByDesc('total_spent')->values();
        $cumulative             = 0;
        $processed              = [];

        foreach ($sortedSuppliers as $supplier) {
            if ($totalSpentAllSuppliers > 0) {
                $pct = ($supplier->total_spent / $totalSpentAllSuppliers) * 100;
                $supplier->spend_percentage = round($pct, 2);
                $cumulative += $pct;
                $supplier->classification = $cumulative <= 80 ? 'A' : ($cumulative <= 95 ? 'B' : 'C');
            }
            $processed[] = $supplier;
        }

        // ─── ABC / delivery / value buckets (from full set) ─────
        $abcCounts        = ['A' => 0, 'B' => 0, 'C' => 0];
        $deliveryRanges   = ['excellent' => 0, 'good' => 0, 'fair' => 0, 'poor' => 0];
        $orderValueRanges = ['high' => 0, 'medium' => 0, 'low' => 0, 'very_low' => 0];

        foreach ($processed as $supplier) {
            $abcCounts[$supplier->classification]++;

            $rate = $supplier->on_time_delivery_rate;
            if ($rate >= 90) $deliveryRanges['excellent']++;
            elseif ($rate >= 75) $deliveryRanges['good']++;
            elseif ($rate >= 60) $deliveryRanges['fair']++;
            else $deliveryRanges['poor']++;

            $avg = $supplier->avg_order_value;
            if ($avg >= 1000) $orderValueRanges['high']++;
            elseif ($avg >= 500) $orderValueRanges['medium']++;
            elseif ($avg >= 100) $orderValueRanges['low']++;
            else $orderValueRanges['very_low']++;
        }

        // ─── Top 10 suppliers by spend ──────────────────────────
        $topSuppliers = collect($processed)->sortByDesc('total_spent')->take(10)->values();
        $topSupplierNames = $topSuppliers->map(fn($s) =>
            mb_strimwidth($s->name, 0, 20, '…')
        )->values();
        $topSupplierSpends = $topSuppliers->pluck('total_spent')->values();

        // ─── Summary ───────────────────────────────────────────
        $totalOrderCount = collect($processed)->sum('total_orders');
        $topSupplier = $processed[0] ?? null;

        $summary = [
            'total_suppliers' => count($processed),
            'total_spent'     => round($totalSpentAllSuppliers, 2),
            'avg_order_value' => $totalOrderCount > 0
                ? round($totalSpentAllSuppliers / $totalOrderCount, 2)
                : 0,
            'top_supplier'    => $topSupplier ? [
                'name'        => $topSupplier->name,
                'total_spent' => $topSupplier->total_spent,
            ] : null,
        ];

        // ─── Pagination ────────────────────────────────────────
        $total    = count($processed);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;
        $pageItems = collect($processed)->slice($offset, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'summary'            => $summary,
                'suppliers'          => $pageItems,
                'abc_counts'         => $abcCounts,
                'delivery_ranges'    => $deliveryRanges,
                'order_value_ranges' => $orderValueRanges,
                'top_supplier_names' => $topSupplierNames,
                'top_supplier_spends'=> $topSupplierSpends,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'from'         => $total > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $total),
                    'per_page'     => $perPage,
                ],
            ],
        ]);
    }
                        

    /**
     * Purchase Order Status Report - Pure Eloquent
     */
    public function purchaseOrderStatus(Request $request)
    {
        $tenantId = $this->getTenantId();

        $startDate  = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate    = $request->get('end_date',   now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        $statusGroup= $request->get('status_group', 'all'); // all | pending | overdue
        $perPage    = (int) $request->get('per_page', 15);

        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('reports.purchasing.purchase-order-status', compact(
            'suppliers',
            'startDate', 'endDate', 'supplierId', 'statusGroup', 'perPage'
        ));
    }

    public function purchaseOrderStatusData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('purchasing reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('purchasing_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate   = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate     = $request->get('end_date',   now()->format('Y-m-d'));
        $supplierId  = $request->get('supplier_id');
        $statusGroup = $request->get('status_group', 'all');
        $perPage     = (int) $request->get('per_page', 15);
        $page        = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        // ─── Status distribution (from period) ─────────────────
        $distributionQuery = PurchaseOrder::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        if ($supplierId) $distributionQuery->where('supplier_id', $supplierId);

        $distribution = $distributionQuery->get()
            ->groupBy('status')
            ->map(fn($items, $status) => [
                'status'      => $status,
                'count'       => $items->count(),
                'total_value' => round((float) $items->sum('total'), 2),
            ])->values();

        // ─── Pending & overdue totals (for KPI cards) ──────────
        $pendingQuery = PurchaseOrder::where('tenant_id', $tenantId)
            ->whereIn('status', ['draft', 'sent', 'pending_approval']);
        if ($supplierId) $pendingQuery->where('supplier_id', $supplierId);
        $pendingTotal = $pendingQuery->count();

        $overdueQuery = PurchaseOrder::where('tenant_id', $tenantId)
            ->whereIn('status', ['sent', 'approved'])
            ->whereNotNull('expected_delivery_date')
            ->where('expected_delivery_date', '<', now());
        if ($supplierId) $overdueQuery->where('supplier_id', $supplierId);
        $overdueTotal = $overdueQuery->count();

        // ─── Main list query (respects statusGroup) ────────────
        $listQuery = PurchaseOrder::with([
                'supplier:id,name,contact_person',
                'location:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        if ($supplierId) $listQuery->where('supplier_id', $supplierId);

        if ($statusGroup === 'pending') {
            $listQuery->whereIn('status', ['draft', 'sent', 'pending_approval']);
        } elseif ($statusGroup === 'overdue') {
            $listQuery->whereIn('status', ['sent', 'approved'])
                ->whereNotNull('expected_delivery_date')
                ->where('expected_delivery_date', '<', now());
        }

        $allOrders = $listQuery->get();

        // ─── Summary (full set) ────────────────────────────────
        $summary = [
            'total_orders'    => $allOrders->count(),
            'total_value'     => round((float) $allOrders->sum('total'), 2),
            'pending_orders'  => $pendingTotal,
            'overdue_orders'  => $overdueTotal,
            'completed_orders'=> $allOrders->where('status', 'completed')->count(),
            'completed_value' => round((float) $allOrders->where('status', 'completed')->sum('total'), 2),
        ];

        // ─── Pagination ────────────────────────────────────────
        $total    = $allOrders->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;

        $rows = $allOrders->sortByDesc('created_at')
            ->slice($offset, $perPage)->values()
            ->map(function ($order) {
                $daysOverdue = null;
                if ($order->expected_delivery_date
                    && $order->expected_delivery_date->lt(now())
                    && in_array($order->status, ['sent', 'approved'])) {
                    $daysOverdue = (int) Carbon::parse($order->expected_delivery_date)->diffInDays(now());
                }

                return (object) [
                    'id'                     => $order->id,
                    'po_number'              => $order->po_number,
                    'supplier_name'          => $order->supplier->name ?? '-',
                    'supplier_contact'       => $order->supplier->contact_person ?? null,
                    'location_name'          => $order->location->name ?? '-',
                    'status'                 => $order->status,
                    'status_label'           => __('passwords.' . $order->status),
                    'status_color'           => match ($order->status) {
                        'draft'            => 'secondary',
                        'sent'             => 'info',
                        'pending_approval' => 'warning',
                        'approved'         => 'primary',
                        'partially_received' => 'success',
                        'received', 'completed' => 'success',
                        'cancelled'        => 'danger',
                        default            => 'dark',
                    },
                    'total'                  => round((float) $order->total, 2),
                    'expected_delivery_date' => $order->expected_delivery_date?->format('Y-m-d'),
                    'days_overdue'           => $daysOverdue,
                    'created_at'             => $order->created_at?->format('Y-m-d'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'summary'        => $summary,
                'distribution'   => $distribution,
                'items'          => $rows,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'from'         => $total > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $total),
                    'per_page'     => $perPage,
                ],
                'meta' => [
                    'start_date'   => $startDate,
                    'end_date'     => $endDate,
                    'status_group' => $statusGroup,
                ],
            ],
        ]);
    }

    public function purchaseOrderStatusDetail($id)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('purchasing reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        $tenantId = $user->tenant_id;

        $order = PurchaseOrder::with([
                'supplier:id,name,contact_person,email,phone,address',
                'location:id,name',
                'creator:id,name',
                'items.productVariant:id,name,sku,product_id',
                'items.productVariant.product:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->firstOrFail();

        $items = $order->items->map(function ($item) {
            $received = (float) ($item->received_quantity ?? 0);
            $ordered  = (float) ($item->quantity ?? 0);
            return [
                'product_name'      => $item->product_name,
                'sku'               => $item->sku,
                'variant_name'      => $item->productVariant?->name,
                'variant_product'   => $item->productVariant?->product?->name,
                'quantity'          => $ordered,
                'received_quantity' => $received,
                'pending_quantity'  => max(0, $ordered - $received),
                'unit_cost'         => round((float) ($item->unit_cost ?? 0), 2),
                'total_cost'        => round((float) ($item->total_cost ?? 0), 2),
                'is_fully_received' => $ordered > 0 && $received >= $ordered,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id'                     => $order->id,
                'po_number'              => $order->po_number,
                'status'                 => $order->status,
                'status_label'           => __('passwords.' . $order->status),
                'status_color'           => match ($order->status) {
                    'draft'              => 'secondary',
                    'sent'               => 'info',
                    'pending_approval'   => 'warning',
                    'approved'           => 'primary',
                    'partially_received' => 'success',
                    'received', 'completed' => 'success',
                    'cancelled'          => 'danger',
                    default              => 'dark',
                },
                'supplier' => [
                    'name'           => $order->supplier?->name ?? '-',
                    'contact_person' => $order->supplier?->contact_person,
                    'email'          => $order->supplier?->email,
                    'phone'          => $order->supplier?->phone,
                    'address'        => $order->supplier?->address,
                ],
                'location'               => $order->location?->name ?? '-',
                'created_by'             => $order->creator?->name ?? '-',
                'created_at'             => $order->created_at?->format('Y-m-d H:i'),
                'updated_at'             => $order->updated_at?->format('Y-m-d H:i'),
                'expected_delivery_date' => $order->expected_delivery_date?->format('Y-m-d'),
                'notes'                  => $order->notes,
                'subtotal'               => round((float) ($order->subtotal ?? 0), 2),
                'tax_total'              => round((float) ($order->tax_total ?? 0), 2),
                'total'                  => round((float) $order->total, 2),
                'items'                  => $items,
            ],
        ]);
    }

    /**
     * Purchase Receipts Report - Pure Eloquent
     */
    public function purchaseReceipts(Request $request)
    {
        $tenantId = $this->getTenantId();

        $startDate  = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate    = $request->get('end_date',   now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        $locationId = $request->get('location_id');
        $perPage    = (int) $request->get('per_page', 15);

        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $locations = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);

        return view('reports.purchasing.purchase-receipts', compact(
            'suppliers', 'locations',
            'startDate', 'endDate', 'supplierId', 'locationId', 'perPage'
        ));
    }

    public function purchaseReceiptsData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('purchasing reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('purchasing_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate  = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate    = $request->get('end_date',   now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        $locationId = $request->get('location_id');
        $perPage    = (int) $request->get('per_page', 15);
        $page       = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $query = PurchaseReceipt::with([
                'purchaseOrder.supplier:id,name,contact_person',
                'purchaseOrder.location:id,name',
                'receiver:id,name',
                'items:id,purchase_receipt_id,purchase_order_item_id,quantity_received,unit_cost',
            ])
            ->whereHas('purchaseOrder', fn($q) => $q->where('tenant_id', $tenantId))
            ->whereBetween('received_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59',
            ]);

        if ($supplierId) $query->whereHas('purchaseOrder', fn($q) => $q->where('supplier_id', $supplierId));
        if ($locationId) $query->whereHas('purchaseOrder', fn($q) => $q->where('location_id', $locationId));

        $allReceipts = $query->get();

        $summary = [
            'total_receipts' => $allReceipts->count(),
            'total_quantity' => round($allReceipts->sum(fn($r) => $r->items->sum('quantity_received')), 2),
            'unique_items'   => $allReceipts->flatMap(fn($r) => $r->items->pluck('purchase_order_item_id'))->unique()->count(),
            'total_value'    => round($allReceipts->sum(fn($r) =>
                $r->items->sum(fn($i) =>
                    ($i->quantity_received ?? 0) * ($i->unit_cost ?? 0)
                )
            ), 2),
        ];

        $total    = $allReceipts->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;

        $rows = $allReceipts->sortByDesc('received_at')
            ->slice($offset, $perPage)->values()
            ->map(function ($r) {
                return (object) [
                    'id'              => $r->id,
                    'receipt_number'  => $r->receipt_number ?? ('#' . $r->id),
                    'po_number'       => $r->purchaseOrder?->po_number,
                    'po_date'         => $r->purchaseOrder?->created_at?->format('Y-m-d'),
                    'supplier_name'   => $r->purchaseOrder?->supplier?->name,
                    'supplier_contact'=> $r->purchaseOrder?->supplier?->contact_person,
                    'location_name'   => $r->purchaseOrder?->location?->name,
                    'received_at'     => $r->received_at?->format('Y-m-d H:i'),
                    'received_by'     => $r->receiver?->name,
                    'items_count'     => $r->items->count(),
                    'total_quantity'  => round($r->items->sum('quantity_received'), 2),
                    'total_value'     => round($r->items->sum(fn($i) =>
                        ($i->quantity_received ?? 0) * ($i->unit_cost ?? 0)
                    ), 2),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'summary'    => $summary,
                'items'      => $rows,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'from'         => $total > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $total),
                    'per_page'     => $perPage,
                ],
            ],
        ]);
    }

    public function purchaseReceiptsDetail($id)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('purchasing reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        $tenantId = $user->tenant_id;

        $receipt = PurchaseReceipt::with([
                'purchaseOrder.supplier:id,name,contact_person,email,phone',
                'purchaseOrder.location:id,name',
                'receiver:id,name',
                'items.purchaseOrderItem.productVariant:id,name,sku,product_id',
                'items.purchaseOrderItem.productVariant.product:id,name',
            ])
            ->whereHas('purchaseOrder', fn($q) => $q->where('tenant_id', $tenantId))
            ->where('id', $id)
            ->firstOrFail();

        $items = $receipt->items->map(function ($item) {
            $poItem      = $item->purchaseOrderItem;
            $ordered     = (float) ($poItem->quantity ?? 0);
            $received    = (float) ($item->quantity_received ?? 0);
            $unitCost    = (float) ($item->unit_cost ?? $poItem->unit_cost ?? 0);

            return [
                'product_name'       => $poItem?->product_name,
                'sku'                => $poItem?->sku,
                'variant_name'       => $poItem?->productVariant?->name,
                'variant_product'    => $poItem?->productVariant?->product?->name,
                'ordered_quantity'   => $ordered,
                'received_quantity'  => $received,
                'pending_quantity'   => max(0, $ordered - $received),
                'unit_cost'          => round($unitCost, 2),
                'total_cost'         => round($received * $unitCost, 2),
                'batch_number'       => $item->batch_number,
                'expiry_date'        => $item->expiry_date
                    ? Carbon::parse($item->expiry_date)->format('Y-m-d')
                    : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id'              => $receipt->id,
                'receipt_number'  => $receipt->receipt_number ?? ('#' . $receipt->id),
                'received_at'     => $receipt->received_at?->format('Y-m-d H:i'),
                'received_by'     => $receipt->receiver?->name ?? 'System',
                'notes'           => $receipt->notes,
                'po_number'       => $receipt->purchaseOrder?->po_number,
                'po_date'         => $receipt->purchaseOrder?->created_at?->format('Y-m-d'),
                'supplier' => [
                    'name'           => $receipt->purchaseOrder?->supplier?->name ?? '-',
                    'contact_person' => $receipt->purchaseOrder?->supplier?->contact_person,
                    'email'          => $receipt->purchaseOrder?->supplier?->email,
                    'phone'          => $receipt->purchaseOrder?->supplier?->phone,
                ],
                'location'        => $receipt->purchaseOrder?->location?->name ?? '-',
                'total_items'     => $items->count(),
                'total_quantity'  => round($items->sum('received_quantity'), 2),
                'total_value'     => round($items->sum('total_cost'), 2),
                'items'           => $items,
            ],
        ]);
    }

    /**
     * Supplier Spend Analysis Report - Pure Eloquent with Fixed Spend Trend
     */
    public function supplierSpendAnalysis(Request $request)
    {
        $tenantId = $this->getTenantId();

        $startDate  = $request->get('start_date', now()->subDays(365)->format('Y-m-d'));
        $endDate    = $request->get('end_date',   now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        $period     = $request->get('period', 'monthly');
        $perPage    = (int) $request->get('per_page', 15);

        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('reports.purchasing.supplier-spend-analysis', compact(
            'suppliers',
            'startDate', 'endDate', 'supplierId', 'period', 'perPage'
        ));
    }

    public function supplierSpendAnalysisData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('purchasing reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('purchasing_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate  = $request->get('start_date', now()->subDays(365)->format('Y-m-d'));
        $endDate    = $request->get('end_date',   now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        $period     = $request->get('period', 'monthly');
        $perPage    = (int) $request->get('per_page', 15);
        $page       = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $query = PurchaseOrder::with(['supplier:id,name,contact_person', 'location:id,name'])
            ->where('tenant_id', $tenantId)
            ->where('status', 'received')
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        if ($supplierId) $query->where('supplier_id', $supplierId);

        $allOrders = $query->get();

        // ─── Per-supplier spend ────────────────────────────────
        $supplierSpendData = $allOrders->groupBy('supplier_id')
            ->map(function ($orders, $sid) {
                $supplier = $orders->first()->supplier;
                $spent    = (float) $orders->sum('total');
                $count    = $orders->count();
                $avg      = $count > 0 ? $spent / $count : 0;
                $min      = (float) ($orders->min('total') ?? 0);
                $max      = (float) ($orders->max('total') ?? 0);
                $lastDate = $orders->max('created_at');

                return (object) [
                    'id'              => $sid,
                    'name'            => $supplier?->name ?? 'Unknown',
                    'contact_person'  => $supplier?->contact_person,
                    'total_spent'     => round($spent, 2),
                    'order_count'     => $count,
                    'avg_order_value' => round($avg, 2),
                    'min_order_value' => round($min, 2),
                    'max_order_value' => round($max, 2),
                    'last_order_date' => $lastDate ? Carbon::parse($lastDate)->format('Y-m-d') : null,
                ];
            })
            ->sortByDesc('total_spent')
            ->values();

        // ─── Summary + ABC classification per supplier ────────
        $totalSpentAll  = (float) $supplierSpendData->sum('total_spent');
        $totalOrdersAll = (int) $supplierSpendData->sum('order_count');

        // Annotate each supplier with spend_percentage and classification
        $supplierSpendData = $supplierSpendData->map(function ($row) use ($totalSpentAll) {
            $pct = $totalSpentAll > 0 ? ($row->total_spent / $totalSpentAll) * 100 : 0;
            $row->spend_percentage = round($pct, 2);
            $row->classification   = $pct >= 70 ? 'A' : ($pct >= 20 ? 'B' : 'C');
            $row->class_color      = $pct >= 70 ? 'danger' : ($pct >= 20 ? 'warning' : 'success');
            return $row;
        });

        $topSupplier = $supplierSpendData->first();

        // ─── Spend trend (period buckets) ─────────────────────
        $trend = [];
        $cursor = Carbon::parse($startDate)->startOfMonth();
        $endObj = Carbon::parse($endDate);

        while ($cursor->lte($endObj)) {
            if ($period === 'quarterly') {
                $q = (int) ceil($cursor->month / 3);
                $key = $cursor->year . '-Q' . $q;
                $cursor->addQuarter();
            } elseif ($period === 'yearly') {
                $key = (string) $cursor->year;
                $cursor->addYear();
            } else {
                $key = $cursor->format('Y-m');
                $cursor->addMonth();
            }
            $trend[$key] = 0.0;
        }

        foreach ($allOrders as $o) {
            $d = Carbon::parse($o->created_at);
            if ($period === 'quarterly') {
                $q = (int) ceil($d->month / 3);
                $key = $d->year . '-Q' . $q;
            } elseif ($period === 'yearly') {
                $key = (string) $d->year;
            } else {
                $key = $d->format('Y-m');
            }
            $trend[$key] = ($trend[$key] ?? 0) + (float) $o->total;
        }
        ksort($trend);

        $spendTrend = [];
        foreach ($trend as $k => $v) {
            $spendTrend[] = ['label' => $k, 'value' => round($v, 2)];
        }

        $summary = [
            'total_suppliers'  => $supplierSpendData->count(),
            'total_spent'      => round($totalSpentAll, 2),
            'total_orders'     => $totalOrdersAll,
            'avg_order_value'  => $totalOrdersAll > 0 ? round($totalSpentAll / $totalOrdersAll, 2) : 0,
            'periods_analyzed' => count($spendTrend),
            'top_supplier'     => $topSupplier ? [
                'name'        => $topSupplier->name,
                'total_spent' => $topSupplier->total_spent,
            ] : null,
        ];

        // ─── Pagination ────────────────────────────────────────
        $total    = $supplierSpendData->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;
        $pageItems = $supplierSpendData->slice($offset, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'summary'    => $summary,
                'suppliers'  => $pageItems,
                'spend_trend'=> $spendTrend,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'from'         => $total > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $total),
                    'per_page'     => $perPage,
                ],
            ],
        ]);
    }

    public function supplierSpendAnalysisDetail($id)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('purchasing reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate = request()->get('start_date', now()->subDays(365)->format('Y-m-d'));
        $endDate   = request()->get('end_date',   now()->format('Y-m-d'));

        $supplier = Supplier::where('tenant_id', $tenantId)->findOrFail($id);

        $orders = PurchaseOrder::with(['location:id,name'])
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $id)
            ->where('status', 'received')
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ])
            ->orderByDesc('created_at')
            ->get();

        $totalSpent = (float) $orders->sum('total');
        $orderCount = $orders->count();
        $avg        = $orderCount > 0 ? $totalSpent / $orderCount : 0;
        $min        = (float) ($orders->min('total') ?? 0);
        $max        = (float) ($orders->max('total') ?? 0);

        $ordersOut = $orders->map(function ($o) {
            return [
                'id'           => $o->id,
                'po_number'    => $o->po_number,
                'created_at'   => $o->created_at?->format('Y-m-d'),
                'status'       => $o->status,
                'status_label' => __('passwords.' . $o->status),
                'status_color' => match ($o->status) {
                    'draft' => 'secondary', 'sent' => 'info',
                    'pending_approval' => 'warning', 'approved' => 'primary',
                    'received', 'completed' => 'success',
                    'cancelled' => 'danger', default => 'dark',
                },
                'location'     => $o->location?->name ?? '-',
                'total'        => round((float) $o->total, 2),
                'notes'        => $o->notes,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'supplier' => [
                    'id'             => $supplier->id,
                    'name'           => $supplier->name,
                    'contact_person' => $supplier->contact_person,
                    'email'          => $supplier->email,
                    'phone'          => $supplier->phone,
                    'address'        => $supplier->address,
                ],
                'summary' => [
                    'total_spent'     => round($totalSpent, 2),
                    'order_count'     => $orderCount,
                    'avg_order_value' => round($avg, 2),
                    'min_order_value' => round($min, 2),
                    'max_order_value' => round($max, 2),
                ],
                'orders' => $ordersOut,
                'meta'   => [
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                ],
            ],
        ]);
    }

    /**
     * Purchase Order Items Analysis - Pure Eloquent
     */
    public function purchaseOrderItems(Request $request)
    {
        $tenantId = $this->getTenantId();

        $startDate        = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate          = $request->get('end_date',   now()->format('Y-m-d'));
        $supplierId       = $request->get('supplier_id');
        $productVariantId = $request->get('variant_id');
        $perPage          = (int) $request->get('per_page', 15);

        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)->with(['product:id,name'])
            ->orderBy('name')->get(['id', 'name', 'sku', 'product_id']);

        return view('reports.purchasing.purchase-order-items', compact(
            'suppliers', 'variants',
            'startDate', 'endDate', 'supplierId', 'productVariantId', 'perPage'
        ));
    }

    public function purchaseOrderItemsData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('purchasing reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('purchasing_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate        = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate          = $request->get('end_date',   now()->format('Y-m-d'));
        $supplierId       = $request->get('supplier_id');
        $productVariantId = $request->get('variant_id');
        $perPage          = (int) $request->get('per_page', 15);
        $page             = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $query = PurchaseOrderItem::with([
                'purchaseOrder.supplier:id,name,contact_person',
                'purchaseOrder.location:id,name',
                'productVariant:id,name,sku,product_id',
                'productVariant.product:id,name',
            ])
            ->whereHas('purchaseOrder', fn($q) => $q
                ->where('tenant_id', $tenantId)
                ->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay(),
                ])
            );
        if ($supplierId)       $query->whereHas('purchaseOrder', fn($q) => $q->where('supplier_id', $supplierId));
        if ($productVariantId) $query->where('product_variant_id', $productVariantId);

        $allItems = $query->get();

        // ─── Summary ───────────────────────────────────────────
        $summary = [
            'total_items'      => $allItems->count(),
            'total_quantity'   => round((float) $allItems->sum('quantity'), 2),
            'total_value'      => round((float) $allItems->sum('total_cost'), 2),
            'avg_unit_cost'    => round((float) ($allItems->avg('unit_cost') ?? 0), 2),
            'unique_products'  => $allItems->unique('product_variant_id')->count(),
            'unique_suppliers' => $allItems->unique(fn($i) => $i->purchaseOrder?->supplier_id)->count(),
        ];

        // ─── Top 10 by quantity ───────────────────────────────
        $topItems = $allItems->groupBy('product_variant_id')->map(function ($group) {
            $first = $group->first();
            $variant = $first->productVariant;
            $label = $variant
                ? (($variant->product?->name ? $variant->product->name . ' – ' : '') . $variant->name)
                : 'N/A';
            return [
                'label'      => mb_strimwidth($label, 0, 30, '…'),
                'quantity'   => round((float) $group->sum('quantity'), 2),
                'total_value'=> round((float) $group->sum('total_cost'), 2),
            ];
        })->sortByDesc('quantity')->take(10)->values();

        // ─── Paginated rows ────────────────────────────────────
        $total    = $allItems->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;

        $rows = $allItems->sortByDesc('created_at')
            ->slice($offset, $perPage)->values()
            ->map(function ($item) {
                $po        = $item->purchaseOrder;
                $variant   = $item->productVariant;
                $ordered   = (float) ($item->quantity ?? 0);
                $received  = (float) ($item->received_quantity ?? 0);
                $balance   = $ordered - $received;

                if ($balance <= 0) {
                    [$statusColor, $statusText] = ['success', __('passwords.completed')];
                } elseif ($received > 0) {
                    [$statusColor, $statusText] = ['warning', __('passwords.partial')];
                } elseif ($po && $po->status === 'cancelled') {
                    [$statusColor, $statusText] = ['danger', __('passwords.cancelled')];
                } else {
                    [$statusColor, $statusText] = ['info', __('passwords.pending')];
                }

                return (object) [
                    'id'               => $item->id,
                    'po_number'        => $po?->po_number,
                    'po_status'        => $po?->status,
                    'supplier_name'    => $po?->supplier?->name,
                    'supplier_contact' => $po?->supplier?->contact_person,
                    'location_name'    => $po?->location?->name,
                    'product_name'     => $item->product_name ?? $variant?->name,
                    'sku'              => $item->sku ?? $variant?->sku,
                    'variant_product'  => $variant?->product?->name,
                    'quantity'         => round($ordered, 2),
                    'unit_cost'        => round((float) ($item->unit_cost ?? 0), 2),
                    'total_cost'       => round((float) ($item->total_cost ?? 0), 2),
                    'received_quantity'=> round($received, 2),
                    'balance_quantity' => round($balance, 2),
                    'received_pct'     => $ordered > 0 ? round(($received / $ordered) * 100, 2) : 0,
                    'order_date'       => $item->created_at?->format('Y-m-d'),
                    'order_time'       => $item->created_at?->format('H:i'),
                    'status_color'     => $statusColor,
                    'status_text'      => $statusText,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'summary'   => $summary,
                'top_items' => $topItems,
                'items'     => $rows,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'from'         => $total > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $total),
                    'per_page'     => $perPage,
                ],
            ],
        ]);
    }

    public function purchaseOrderItemsDetail($id)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('purchasing reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        $tenantId = $user->tenant_id;

        $item = PurchaseOrderItem::with([
                'purchaseOrder.supplier:id,name,contact_person,email,phone',
                'purchaseOrder.location:id,name',
                'purchaseOrder.creator:id,name',
                'productVariant:id,name,sku,product_id',
                'productVariant.product:id,name',
                'paymentMethod:id,name',
            ])
            ->whereHas('purchaseOrder', fn($q) => $q->where('tenant_id', $tenantId))
            ->where('id', $id)
            ->firstOrFail();

        $po       = $item->purchaseOrder;
        $variant  = $item->productVariant;
        $ordered  = (float) ($item->quantity ?? 0);
        $received = (float) ($item->received_quantity ?? 0);

        return response()->json([
            'success' => true,
            'data' => [
                'id'                => $item->id,
                'product_name'      => $item->product_name ?? $variant?->name,
                'sku'               => $item->sku ?? $variant?->sku,
                'variant_name'      => $variant?->name,
                'variant_product'   => $variant?->product?->name,
                'quantity'          => round($ordered, 2),
                'received_quantity' => round($received, 2),
                'balance_quantity'  => round($ordered - $received, 2),
                'unit_cost'         => round((float) ($item->unit_cost ?? 0), 2),
                'total_cost'        => round((float) ($item->total_cost ?? 0), 2),
                'payment_status'    => $item->payment_status,
                'payment_method'    => $item->paymentMethod?->name,
                'payment_date'      => $item->payment_date
                    ? Carbon::parse($item->payment_date)->format('Y-m-d')
                    : null,
                'notes'             => $item->notes,
                'created_at'        => $item->created_at?->format('Y-m-d H:i'),
                'po' => $po ? [
                    'po_number'     => $po->po_number,
                    'status'        => $po->status,
                    'status_label'  => __('passwords.' . $po->status),
                    'status_color'  => match ($po->status) {
                        'draft' => 'secondary', 'sent' => 'info',
                        'pending_approval' => 'warning', 'approved' => 'primary',
                        'received', 'completed' => 'success',
                        'cancelled' => 'danger', default => 'dark',
                    },
                    'location_name' => $po->location?->name ?? '-',
                    'created_at'    => $po->created_at?->format('Y-m-d'),
                    'created_by'    => $po->creator?->name ?? '-',
                    'supplier' => [
                        'name'           => $po->supplier?->name ?? '-',
                        'contact_person' => $po->supplier?->contact_person,
                        'email'          => $po->supplier?->email,
                        'phone'          => $po->supplier?->phone,
                    ],
                ] : null,
            ],
        ]);
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

        $startDate     = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate       = $request->get('end_date',   now()->format('Y-m-d'));
        $paymentStatus = $request->get('payment_status', 'all');
        $supplierId    = $request->get('supplier_id');
        $perPage       = (int) $request->get('per_page', 15);

        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('reports.purchasing.payment-status', compact(
            'suppliers',
            'startDate', 'endDate', 'paymentStatus', 'supplierId', 'perPage'
        ));
    }

    public function paymentStatusData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('purchasing reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('purchasing_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate     = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate       = $request->get('end_date',   now()->format('Y-m-d'));
        $paymentStatus = $request->get('payment_status', 'all');
        $supplierId    = $request->get('supplier_id');
        $perPage       = (int) $request->get('per_page', 15);
        $page          = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $query = PurchaseOrderItem::with([
                'purchaseOrder.supplier:id,name,contact_person',
                'purchaseOrder.location:id,name',
                'paymentMethod:id,name',
                'productVariant:id,name,sku,product_id',
                'productVariant.product:id,name',
            ])
            ->whereHas('purchaseOrder', fn($q) => $q
                ->where('tenant_id', $tenantId)
                ->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay(),
                ])
            );

        if ($supplierId) {
            $query->whereHas('purchaseOrder', fn($q) => $q->where('supplier_id', $supplierId));
        }

        // Status filter is applied AFTER we derive the status — see below.

        $allItems = $query->get();

        // ═══════════════════════════════════════════════════════════════
        // Preload ALL payment transactions for these POs in one query,
        // so each row can be enriched with the latest real payment.
        // ═══════════════════════════════════════════════════════════════
        $poIds = $allItems->pluck('purchaseOrder.id')->filter()->unique()->values();

        $latestPaymentByPo = \App\Models\PaymentTransactionLog::query()
            ->where('tenant_id', $tenantId)
            ->where('reference_table', 'purchase_orders')
            ->whereIn('reference_id', $poIds)
            ->where('status', 'COMPLETED')
            ->with('paymentMethod:id,name')
            ->orderByDesc('transaction_date')
            ->get()
            ->groupBy('reference_id')
            ->map(fn($group) => $group->first());   // latest per PO

        // ═══════════════════════════════════════════════════════════════
        // Build a *derived-status* view of every row, then filter.
        // ═══════════════════════════════════════════════════════════════
        $enriched = $allItems->map(function ($item) use ($latestPaymentByPo) {
            $po            = $item->purchaseOrder;
            $variant       = $item->productVariant;
            $total         = (float) ($item->total_cost ?? 0);
            $paid          = (float) ($item->paid_total ?? 0);

            // ═══════════════════════════════════════════════════════════
            // Authoritative status
            // ═══════════════════════════════════════════════════════════
            if ($paid <= 0) {
                $status = 'pending';
            } elseif ($paid >= $total && $total > 0) {
                $status = 'paid';
            } else {
                $status = 'partial';
            }

            $latestPayment = $po ? $latestPaymentByPo->get($po->id) : null;

            // Due date: 30 days after the first real payment (or receipt)
            $dueDate = null;
            if ($latestPayment?->transaction_date) {
                $dueDate = Carbon::parse($latestPayment->transaction_date)->addDays(30);
            } elseif ($item->payment_date) {
                $dueDate = Carbon::parse($item->payment_date)->addDays(30);
            }
            if ($status !== 'paid' && $dueDate && $dueDate->isPast()) {
                $status = 'overdue';
            }

            // Resolve payment method — from the real transaction, then from
            // the item's own column, then from the receipt line.
            $paymentMethod = $latestPayment?->paymentMethod?->name
                        ?? $item->paymentMethod?->name
                        ?? null;

            return (object) [
                'id'               => $item->id,
                'po'               => $po,
                'variant'          => $variant,
                'total_cost'       => round($total, 2),
                'amount_paid'      => round($paid, 2),
                'balance'          => round(max(0, $total - $paid), 2),
                'payment_status'   => $status,
                'payment_method'   => $paymentMethod,
                'payment_date'     => $latestPayment?->transaction_date?->format('Y-m-d')
                                    ?? $item->payment_date?->format('Y-m-d'),
                'due_date'         => $dueDate?->format('Y-m-d'),
                'has_real_payment' => (bool) $latestPayment,
                'payment_reference'=> $latestPayment ? 'TXN #' . $latestPayment->id : null,
            ];
        });

        // Apply the derived-status filter
        if ($paymentStatus !== 'all') {
            $enriched = $enriched->where('payment_status', $paymentStatus)->values();
        }

        // ═══════════════════════════════════════════════════════════════
        // Summary
        // ═══════════════════════════════════════════════════════════════
        $statusBuckets = $enriched->groupBy('payment_status')->map(function ($items, $status) {
            return [
                'status'       => $status,
                'count'        => $items->count(),
                'total_amount' => round((float) $items->sum('total_cost'), 2),
            ];
        })->values();

        $overdueItems = $enriched->where('payment_status', 'overdue')->values();

        $totalAmountDue  = (float) $enriched->where('payment_status', '!=', 'paid')->sum('total_cost');
        $totalAmountPaid = (float) $enriched->where('payment_status', 'paid')->sum('total_cost');
        $overdueAmount   = (float) $overdueItems->sum('total_cost');

        $summary = [
            'total_amount_due'  => round($totalAmountDue, 2),
            'total_amount_paid' => round($totalAmountPaid, 2),
            'overdue_amount'    => round($overdueAmount, 2),
            'overdue_count'     => $overdueItems->count(),
            'total_items'       => $enriched->count(),
        ];

        // ═══════════════════════════════════════════════════════════════
        // Chart data
        // ═══════════════════════════════════════════════════════════════
        $chartOrder = ['pending', 'partial', 'paid', 'overdue', 'cancelled'];
        $chartColors = [
            'pending' => '#FFC700', 'partial' => '#17a2b8',
            'paid'    => '#50CD89', 'overdue' => '#F1416C', 'cancelled' => '#6c757d',
        ];
        $chartData = [];
        foreach ($chartOrder as $s) {
            $amount = (float) $enriched->where('payment_status', $s)->sum('total_cost');
            if ($amount > 0) {
                $chartData[] = [
                    'label' => __('passwords.' . $s),
                    'value' => round($amount, 2),
                    'color' => $chartColors[$s],
                ];
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // Paginate
        // ═══════════════════════════════════════════════════════════════
        $total    = $enriched->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;

        $rows = $enriched
            ->sortByDesc('payment_date')
            ->slice($offset, $perPage)
            ->values()
            ->map(function ($r) {
                $po      = $r->po;
                $variant = $r->variant;
                $status  = $r->payment_status;

                // Days info
                $daysInfo  = '';
                $daysColor = 'secondary';
                if ($status === 'paid') {
                    $daysInfo  = __('passwords.paid');
                    $daysColor = 'success';
                } elseif ($r->due_date) {
                    $due = Carbon::parse($r->due_date);
                    if ($due->isPast()) {
                        $daysInfo  = (int) $due->diffInDays(now()) . ' ' . __('passwords.days_overdue');
                        $daysColor = 'danger';
                    } else {
                        $daysRemaining = (int) now()->diffInDays($due);
                        $daysInfo  = $daysRemaining . ' ' . __('passwords.days_left');
                        $daysColor = $daysRemaining <= 7 ? 'warning' : 'success';
                    }
                }

                return [
                    'id'                => $r->id,
                    'po_number'         => $po?->po_number,
                    'po_created_at'     => $po?->created_at?->format('Y-m-d'),
                    'supplier_name'     => $po?->supplier?->name,
                    'supplier_contact'  => $po?->supplier?->contact_person,
                    'product_name'      => $po ? $variant?->name : null,
                    'sku'               => $variant?->sku,
                    'quantity'          => (float) ($po ? (float) ($r->amount_paid >= 0 ? 0 : 0) : 0),
                    'total_cost'        => $r->total_cost,
                    'amount_paid'       => $r->amount_paid,
                    'balance'           => $r->balance,
                    'payment_date'      => $r->payment_date,
                    'due_date'          => $r->due_date,
                    'payment_method'    => $r->payment_method,
                    'payment_reference' => $r->payment_reference,
                    'has_real_payment'  => $r->has_real_payment,
                    'payment_status'    => $status,
                    'status_label'      => __('passwords.' . $status),
                    'status_color'      => match ($status) {
                        'pending' => 'warning', 'partial' => 'info',
                        'paid'    => 'success', 'overdue' => 'danger',
                        'cancelled' => 'dark', default => 'secondary',
                    },
                    'days_info'         => $daysInfo,
                    'days_color'        => $daysColor,
                    'is_overdue'        => $status === 'overdue',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'summary'    => $summary,
                'status'     => $statusBuckets,
                'chart'      => $chartData,
                'items'      => $rows,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'from'         => $total > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $total),
                    'per_page'     => $perPage,
                ],
            ],
        ]);
    }

    public function paymentStatusDetail($id)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('purchasing reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        $tenantId = $user->tenant_id;

        $item = PurchaseOrderItem::with([
                'purchaseOrder.supplier:id,name,contact_person,email,phone',
                'purchaseOrder.location:id,name',
                'purchaseOrder.creator:id,name',
                'productVariant:id,name,sku,product_id',
                'productVariant.product:id,name',
                'paymentMethod:id,name',
            ])
            ->whereHas('purchaseOrder', fn($q) => $q->where('tenant_id', $tenantId))
            ->where('id', $id)
            ->firstOrFail();

        $po      = $item->purchaseOrder;
        $total   = (float) ($item->total_cost ?? 0);
        $paid    = (float) ($item->paid_total ?? 0);
        $balance = max(0, $total - $paid);

        // Latest real payment for this PO
        $latestPayment = \App\Models\PaymentTransactionLog::query()
            ->where('tenant_id', $tenantId)
            ->where('reference_table', 'purchase_orders')
            ->where('reference_id', $po?->id)
            ->where('status', 'COMPLETED')
            ->orderByDesc('transaction_date')
            ->with('paymentMethod:id,name')
            ->first();

        // Receipt line for this PO item
        $receiptLine = \App\Models\PurchaseReceiptItem::query()
            ->where('tenant_id', $tenantId)
            ->where('purchase_order_item_id', $item->id)
            ->orderByDesc('id')
            ->first();

        // ─── Derive status from money ───
        if ($paid <= 0) {
            $derivedStatus = 'pending';
        } elseif ($paid >= $total && $total > 0) {
            $derivedStatus = 'paid';
        } else {
            $derivedStatus = 'partial';
        }

        // Due date + overdue override
        $dueDate = $latestPayment?->transaction_date
            ? Carbon::parse($latestPayment->transaction_date)->addDays(30)
            : ($receiptLine?->payment_date
                ? Carbon::parse($receiptLine->payment_date)->addDays(30)
                : null);
        if ($derivedStatus !== 'paid' && $dueDate && $dueDate->isPast()) {
            $derivedStatus = 'overdue';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'               => $item->id,
                'product_name'     => $item->product_name ?? $item->productVariant?->name,
                'sku'              => $item->sku ?? $item->productVariant?->sku,
                'variant_product'  => $item->productVariant?->product?->name,
                'quantity'         => (float) ($item->quantity ?? 0),
                'unit_cost'        => round((float) ($item->unit_cost ?? 0), 2),
                'total_cost'       => round($total, 2),
                'amount_paid'      => round($paid, 2),
                'balance'          => round($balance, 2),

                'payment_status'   => $derivedStatus,
                'status_label'     => __('passwords.' . $derivedStatus),
                'status_color'     => match ($derivedStatus) {
                    'pending' => 'warning', 'partial' => 'info',
                    'paid'    => 'success', 'overdue' => 'danger',
                    default => 'secondary',
                },

                'payment_method'    => $latestPayment?->paymentMethod?->name
                                    ?? $receiptLine?->paymentMethod?->name
                                    ?? null,
                'payment_date'      => $latestPayment?->transaction_date?->format('Y-m-d')
                                    ?? $receiptLine?->payment_date?->format('Y-m-d')
                                    ?? null,
                'payment_reference' => $latestPayment ? 'TXN #' . $latestPayment->id : null,
                'due_date'          => $dueDate?->format('Y-m-d'),
                'has_real_payment'  => (bool) $latestPayment,

                'notes'      => $item->notes,
                'created_at' => $item->created_at?->format('Y-m-d H:i'),
                'po' => $po ? [
                    'po_number'     => $po->po_number,
                    'status'        => $po->status,
                    'status_label'  => __('passwords.' . $po->status),
                    'location_name' => $po->location?->name ?? '-',
                    'created_at'    => $po->created_at?->format('Y-m-d'),
                    'created_by'    => $po->creator?->name ?? '-',
                    'supplier' => [
                        'name'           => $po->supplier?->name ?? '-',
                        'contact_person' => $po->supplier?->contact_person,
                        'email'          => $po->supplier?->email,
                        'phone'          => $po->supplier?->phone,
                    ],
                ] : null,
            ],
        ]);
    }

    /**
     * Received Inventory Report - Pure Eloquent
     */
    public function receivedInventory(Request $request)
    {
        $tenantId = $this->getTenantId();

        $startDate        = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate          = $request->get('end_date', now()->format('Y-m-d'));
        $supplierId       = $request->get('supplier_id');
        $productVariantId = $request->get('variant_id');
        $batchNumber      = $request->get('batch_number');
        $includeExpiring  = $request->boolean('include_expiring');
        $perPage          = (int) $request->get('per_page', 15);

        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)->with('product:id,name')
            ->orderBy('name')->get(['id', 'name', 'sku', 'product_id']);

        $batchNumbers = ReceivedProductVariant::where('tenant_id', $tenantId)
            ->whereNotNull('batch_number')
            ->where('batch_number', '!=', '')
            ->select('batch_number')->distinct()->orderBy('batch_number')->pluck('batch_number');

        return view('reports.purchasing.received-inventory', compact(
            'suppliers', 'variants', 'batchNumbers',
            'startDate', 'endDate', 'supplierId', 'productVariantId',
            'batchNumber', 'includeExpiring', 'perPage'
        ));
    }

    public function receivedInventoryData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('purchasing reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('purchasing_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate        = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate          = $request->get('end_date', now()->format('Y-m-d'));
        $supplierId       = $request->get('supplier_id');
        $productVariantId = $request->get('variant_id');
        $batchNumber      = $request->get('batch_number');
        $includeExpiring  = $request->boolean('include_expiring');
        $perPage          = (int) $request->get('per_page', 15);
        $page             = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $query = ReceivedProductVariant::with([
                'purchaseOrder.supplier:id,name,contact_person',
                'purchaseOrder.location:id,name',
                'productVariant:id,name,sku,product_id',
                'productVariant.product:id,name',
                'receiver:id,name,email',
            ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59',
            ]);

        if ($supplierId) {
            $query->whereHas('purchaseOrder', fn($q) => $q->where('supplier_id', $supplierId));
        }
        if ($productVariantId) {
            $query->where('product_variant_id', $productVariantId);
        }
        if ($batchNumber) {
            $query->where('batch_number', 'LIKE', '%' . $batchNumber . '%');
        }
        if ($includeExpiring) {
            $query->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', now()->addDays(30));
        }

        $allItems = $query->get();

        // ─── Summary ────────────────────────────────────
        $summary = [
            'total_items'      => $allItems->count(),
            'total_quantity'   => round((float) $allItems->sum('quantity_received'), 2),
            'total_value'      => round((float) $allItems->sum('total_cost'), 2),
            'unique_products'  => $allItems->unique('product_variant_id')->count(),
            'unique_suppliers' => $allItems->unique(fn($i) => $i->purchaseOrder?->supplier_id)->count(),
            'expiring_soon'    => $allItems->filter(fn($i) =>
                $i->expiry_date
                && Carbon::parse($i->expiry_date)->lte(now()->addDays(30))
                && Carbon::parse($i->expiry_date)->gt(now())
            )->count(),
        ];

        // ─── Batch analysis ─────────────────────────────
        $batchAnalysis = $allItems->groupBy('batch_number')->map(function ($items, $batch) {
            $first        = $items->first();
            $expiry       = $first->expiry_date ? Carbon::parse($first->expiry_date) : null;
            $daysToExpiry = $expiry ? (int) now()->diffInDays($expiry, false) : null;

            return [
                'batch_number'   => $batch ?: null,
                'total_quantity' => round((float) $items->sum('quantity_received'), 2),
                'total_value'    => round((float) $items->sum('total_cost'), 2),
                'expiry_date'    => $expiry?->format('Y-m-d'),
                'days_to_expiry' => $daysToExpiry,
            ];
        })->sortByDesc('total_quantity')->values();

        // ─── Paginate ───────────────────────────────────
        $total    = $allItems->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;

        $rows = $allItems->sortByDesc('created_at')->slice($offset, $perPage)->values()
            ->map(function ($item) {
                $po      = $item->purchaseOrder;
                $variant = $item->productVariant;
                $expiry  = $item->expiry_date ? Carbon::parse($item->expiry_date) : null;
                $daysToExpiry = $expiry ? (int) now()->diffInDays($expiry, false) : null;

                $expiryColor = 'success';
                $expiryText  = __('passwords.not_expiring_soon');
                if ($daysToExpiry !== null) {
                    if ($daysToExpiry <= 0)      { $expiryColor = 'danger';  $expiryText = __('passwords.expired'); }
                    elseif ($daysToExpiry <= 7)  { $expiryColor = 'danger';  $expiryText = __('passwords.expiring_soon'); }
                    elseif ($daysToExpiry <= 30) { $expiryColor = 'warning'; $expiryText = __('passwords.expiring_soon'); }
                }

                return [
                    'id'               => $item->id,
                    'po_number'        => $po?->po_number,
                    'po_date'          => $item->created_at?->format('Y-m-d'),
                    'product_name'     => $variant?->name,
                    'sku'              => $variant?->sku,
                    'variant_product'  => $variant?->product?->name,
                    'supplier_name'    => $po?->supplier?->name,
                    'supplier_contact' => $po?->supplier?->contact_person,
                    'quantity'         => round((float) $item->quantity_received, 2),
                    'unit_cost'        => round((float) $item->unit_cost, 2),
                    'total_cost'       => round((float) $item->total_cost, 2),
                    'batch_number'     => $item->batch_number,
                    'expiry_date'      => $expiry?->format('Y-m-d'),
                    'days_to_expiry'   => $daysToExpiry,
                    'expiry_color'     => $expiryColor,
                    'expiry_text'      => $expiryText,
                    'received_by'      => $item->receiver?->name,
                    'received_by_email'=> $item->receiver?->email,
                    'received_at'      => $item->created_at?->format('Y-m-d H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'summary'       => $summary,
                'batch_analysis'=> $batchAnalysis,
                'items'         => $rows,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'from'         => $total > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $total),
                    'per_page'     => $perPage,
                ],
                'meta' => [
                    'start_date'       => $startDate,
                    'end_date'         => $endDate,
                    'include_expiring' => $includeExpiring,
                ],
            ],
        ]);
    }



    /**
     * Supplier Risk Assessment Report - Pure Eloquent with Pagination
     */
    public function supplierRiskAssessment(Request $request)
    {
        $tenantId = $this->getTenantId();

        $perPage         = (int) $request->get('per_page', 15);
        $criticalPerPage = (int) $request->get('critical_per_page', 10);

        return view('reports.purchasing.supplier-risk-assessment', compact(
            'perPage', 'criticalPerPage'
        ));
    }

    public function supplierRiskAssessmentData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('purchasing reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('purchasing_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $perPage         = (int) $request->get('per_page', 15);
        $criticalPerPage = (int) $request->get('critical_per_page', 10);
        $page            = max(1, (int) $request->get('page', 1));
        $criticalPage    = max(1, (int) $request->get('critical_page', 1));

        // ─── Load suppliers + aggregate stats in ONE query each ────
        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        if ($suppliers->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'summary'       => ['total_suppliers' => 0, 'high' => 0, 'medium' => 0, 'low' => 0,
                                        'high_pct' => 0, 'medium_pct' => 0, 'low_pct' => 0],
                    'risk_stats'    => ['avg' => 0, 'max' => 0, 'min' => 0],
                    'chart'         => ['labels' => [], 'values' => [], 'colors' => []],
                    'critical'      => ['items' => [], 'pagination' => null],
                    'suppliers'     => ['items' => [], 'pagination' => null],
                ],
            ]);
        }

        $supplierIds = $suppliers->pluck('id');

        // 1) all-time aggregates per supplier
        $aggregates = PurchaseOrder::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('supplier_id', $supplierIds)
            ->selectRaw('
                supplier_id,
                COUNT(*) as total_orders,
                COALESCE(SUM(total), 0) as total_spent,
                SUM(CASE WHEN status = "received" THEN 1 ELSE 0 END) as delivered_orders,
                AVG(CASE WHEN status = "received" AND received_at IS NOT NULL
                        THEN DATEDIFF(received_at, created_at) END) as avg_delivery_days,
                MAX(created_at) as last_order_date
            ')
            ->groupBy('supplier_id')
            ->get()
            ->keyBy('supplier_id');

        // 2) recent 10 orders per supplier (for frequency calc)
        //    Simple approach: fetch all recent orders for the suppliers, group in PHP.
        $recentOrders = PurchaseOrder::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('supplier_id', $supplierIds)
            ->orderByDesc('created_at')
            ->get(['id', 'supplier_id', 'created_at'])
            ->groupBy('supplier_id')
            ->map(fn($g) => $g->take(10));

        // ─── Build per-supplier risk rows ─────────────────────────
        $rows = $suppliers->map(function ($supplier) use ($aggregates, $recentOrders) {
            $agg = $aggregates->get($supplier->id);
            $totalOrders   = (int)   ($agg->total_orders     ?? 0);
            $totalSpent    = (float) ($agg->total_spent      ?? 0);
            $deliveredCnt  = (int)   ($agg->delivered_orders ?? 0);
            $avgDelivery   = (float) ($agg->avg_delivery_days ?? 0);
            $lastOrderDate = $agg->last_order_date ?? null;

            $recent = $recentOrders->get($supplier->id) ?? collect();
            $orderFrequency = $totalOrders > 0 ? $recent->count() / 30 : 0;

            // ── Risk factors (same logic as before) ──
            $riskScore = 0;
            $riskFactors = [];

            $orderFrequencyRisk = max(0, 30 - ($orderFrequency * 10));
            $riskScore += $orderFrequencyRisk;
            if ($orderFrequencyRisk > 15) $riskFactors[] = 'low_order_frequency';

            $paymentTerms = $supplier->payment_terms ?? 30;
            $paymentTermRisk = $paymentTerms > 60 ? 30 : ($paymentTerms > 45 ? 25 : ($paymentTerms > 30 ? 20 : 10));
            $riskScore += $paymentTermRisk;
            if ($paymentTerms > 45) $riskFactors[] = 'extended_payment_terms';

            $isCritical = $totalSpent > 10000;
            $sourcingRisk = $isCritical ? 25 : 5;
            $riskScore += $sourcingRisk;
            if ($isCritical) $riskFactors[] = 'single_sourcing_risk';

            $deliveryRisk = $avgDelivery > 21 ? 25 : ($avgDelivery > 14 ? 20 : ($avgDelivery > 7 ? 10 : 0));
            $riskScore += $deliveryRisk;
            if ($avgDelivery > 14) $riskFactors[] = 'slow_delivery';

            $geographicRisk = ($supplier->country_code
                && !in_array(strtoupper($supplier->country_code), ['US', 'CA', 'MX'])) ? 15 : 0;
            $riskScore += $geographicRisk;
            if ($geographicRisk > 0) $riskFactors[] = 'international_supplier';

            $riskLevel = $riskScore >= 70 ? 'high' : ($riskScore >= 40 ? 'medium' : 'low');
            $daysSinceLastOrder = $lastOrderDate
                ? Carbon::parse($lastOrderDate)->diffInDays(now())
                : null;

            return [
                'id'                    => $supplier->id,
                'name'                  => $supplier->name,
                'contact_person'        => $supplier->contact_person,
                'email'                 => $supplier->email,
                'phone'                 => $supplier->phone,
                'country_code'          => $supplier->country_code,
                'payment_terms_days'    => $paymentTerms,
                'total_orders'          => $totalOrders,
                'total_spent'           => round($totalSpent, 2),
                'risk_score'            => round(min(100, $riskScore), 1),
                'risk_level'            => $riskLevel,
                'risk_factors'          => $riskFactors,
                'avg_delivery_days'     => round($avgDelivery, 1),
                'delivered_orders'      => $deliveredCnt,
                'order_frequency'       => round($orderFrequency, 2),
                'last_order_date'       => $lastOrderDate,
                'days_since_last_order' => $daysSinceLastOrder,
            ];
        })->sortByDesc('risk_score')->values();

        // ─── Distribution + stats ─────────────────────────────────
        $distribution = [
            'high'   => $rows->where('risk_level', 'high')->count(),
            'medium' => $rows->where('risk_level', 'medium')->count(),
            'low'    => $rows->where('risk_level', 'low')->count(),
        ];
        $totalSuppliers = $rows->count();
        $pct = fn($n) => $totalSuppliers > 0 ? round(($n / $totalSuppliers) * 100, 1) : 0;

        // ─── Critical suppliers (high + >5000 spent) ──────────────
        $criticalAll = $rows->filter(fn($s) => $s['risk_level'] === 'high' && $s['total_spent'] > 5000)->values();
        $criticalTotal   = $criticalAll->count();
        $criticalLastPage = max(1, (int) ceil($criticalTotal / $criticalPerPage));
        $criticalPage     = min($criticalPage, $criticalLastPage);
        $criticalOffset   = ($criticalPage - 1) * $criticalPerPage;
        $criticalItems    = $criticalAll->slice($criticalOffset, $criticalPerPage)->values();

        // ─── Main suppliers pagination ────────────────────────────
        $mainTotal    = $rows->count();
        $mainLastPage = max(1, (int) ceil($mainTotal / $perPage));
        $page         = min($page, $mainLastPage);
        $mainOffset   = ($page - 1) * $perPage;
        $mainItems    = $rows->slice($mainOffset, $perPage)->values();

        $riskScores = $rows->pluck('risk_score');

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_suppliers' => $totalSuppliers,
                    'high'            => $distribution['high'],
                    'medium'          => $distribution['medium'],
                    'low'             => $distribution['low'],
                    'high_pct'        => $pct($distribution['high']),
                    'medium_pct'      => $pct($distribution['medium']),
                    'low_pct'         => $pct($distribution['low']),
                ],
                'risk_stats' => [
                    'avg' => round($riskScores->avg() ?? 0, 2),
                    'max' => round($riskScores->max() ?? 0, 2),
                    'min' => round($riskScores->min() ?? 0, 2),
                ],
                'chart' => [
                    'labels' => [__('passwords.high_risk'), __('passwords.medium_risk'), __('passwords.low_risk')],
                    'values' => [$distribution['high'], $distribution['medium'], $distribution['low']],
                    'colors' => ['#F1416C', '#FFC700', '#50CD89'],
                ],
                'critical' => [
                    'items' => $criticalItems,
                    'pagination' => [
                        'current_page' => $criticalPage,
                        'last_page'    => $criticalLastPage,
                        'total'        => $criticalTotal,
                        'from'         => $criticalTotal > 0 ? $criticalOffset + 1 : 0,
                        'to'           => min($criticalOffset + $criticalPerPage, $criticalTotal),
                        'per_page'     => $criticalPerPage,
                        'page_param'   => 'critical_page',
                        'per_page_param' => 'critical_per_page',
                    ],
                ],
                'suppliers' => [
                    'items' => $mainItems,
                    'pagination' => [
                        'current_page' => $page,
                        'last_page'    => $mainLastPage,
                        'total'        => $mainTotal,
                        'from'         => $mainTotal > 0 ? $mainOffset + 1 : 0,
                        'to'           => min($mainOffset + $perPage, $mainTotal),
                        'per_page'     => $perPage,
                        'page_param'   => 'page',
                        'per_page_param' => 'per_page',
                    ],
                ],
            ],
        ]);
    }


    /**
     * Purchase Cost Analysis Report - Pure Eloquent
     */
    public function purchaseCostAnalysis(Request $request)
    {
        $tenantId = $this->getTenantId();

        $startDate        = $request->get('start_date', now()->subDays(180)->format('Y-m-d'));
        $endDate          = $request->get('end_date',   now()->format('Y-m-d'));
        $productVariantId = $request->get('variant_id');
        $supplierId       = $request->get('supplier_id');
        $perPage          = (int) $request->get('per_page', 15);

        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)->with('product:id,name')
            ->orderBy('name')->get(['id', 'name', 'sku', 'product_id']);

        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('reports.purchasing.purchase-cost-analysis', compact(
            'variants', 'suppliers',
            'startDate', 'endDate', 'productVariantId', 'supplierId', 'perPage'
        ));
    }

    public function purchaseCostAnalysisData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('purchasing reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('purchasing_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate        = $request->get('start_date', now()->subDays(180)->format('Y-m-d'));
        $endDate          = $request->get('end_date',   now()->format('Y-m-d'));
        $productVariantId = $request->get('variant_id');
        $supplierId       = $request->get('supplier_id');
        $perPage          = (int) $request->get('per_page', 15);
        $page             = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $query = ReceivedProductVariant::with([
                'productVariant:id,name,sku,product_id',
                'productVariant.product:id,name,category_id',
                'productVariant.product.category:id,name',
                'purchaseOrder.supplier:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59',
            ]);

        if ($productVariantId) {
            $query->where('product_variant_id', $productVariantId);
        }
        if ($supplierId) {
            $query->whereHas('purchaseOrder', fn($q) => $q->where('supplier_id', $supplierId));
        }

        $allItems = $query->get();

        // ─── Per-variant analysis ────────────────────────────────
        $daysInPeriod = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;

        $productAnalysis = $allItems->groupBy('product_variant_id')->map(function ($items, $variantId) use ($daysInPeriod) {
            $variant = $items->first()->productVariant;
            $sorted  = $items->sortBy('created_at');

            $oldestPrice = (float) ($sorted->first()->unit_cost ?? 0);
            $newestPrice = (float) ($sorted->last()->unit_cost  ?? 0);

            $totalQuantity = (float) $items->sum('quantity_received');
            $totalCost     = (float) $items->sum('total_cost');
            $avgUnitCost   = $totalQuantity > 0 ? $totalCost / $totalQuantity : 0;
            $priceChange   = $newestPrice - $oldestPrice;
            $priceChangePct = $oldestPrice > 0 ? ($priceChange / $oldestPrice) * 100 : 0;
            $purchaseCount = $items->count();

            // Trend bucket
            if ($priceChangePct > 5)       { $trendColor = 'danger';  $trendKey = 'significant_increase'; }
            elseif ($priceChangePct > 0)   { $trendColor = 'warning'; $trendKey = 'moderate_increase'; }
            elseif ($priceChangePct < -5)  { $trendColor = 'success'; $trendKey = 'significant_decrease'; }
            elseif ($priceChangePct < 0)   { $trendColor = 'success'; $trendKey = 'moderate_decrease'; }
            else                            { $trendColor = 'info';    $trendKey = 'stable'; }

            // Cost efficiency
            $avgCostPerPurchase = $purchaseCount > 0 ? $totalCost / $purchaseCount : 0;
            if ($avgCostPerPurchase > 1000)      { $effKey = 'low';    $effColor = 'danger'; }
            elseif ($avgCostPerPurchase > 500)   { $effKey = 'medium'; $effColor = 'warning'; }
            else                                  { $effKey = 'high';   $effColor = 'success'; }

            // Purchase frequency per month
            $freqPerMonth = $daysInPeriod > 0 ? ($purchaseCount / $daysInPeriod) * 30 : 0;
            if ($freqPerMonth > 4)        { $freqKey = 'very_high'; $freqColor = 'primary'; }
            elseif ($freqPerMonth > 2)    { $freqKey = 'high';      $freqColor = 'success'; }
            elseif ($freqPerMonth > 1)    { $freqKey = 'moderate';  $freqColor = 'info'; }
            elseif ($freqPerMonth > 0.5)  { $freqKey = 'low';       $freqColor = 'warning'; }
            else                           { $freqKey = 'very_low';  $freqColor = 'danger'; }

            return [
                'variant_id'                => $variantId,
                'product_name'              => $variant?->name,
                'sku'                       => $variant?->sku,
                'product_name_parent'       => $variant?->product?->name,
                'category_name'             => $variant?->product?->category?->name,
                'total_quantity'            => round($totalQuantity, 2),
                'total_cost'                => round($totalCost, 2),
                'avg_unit_cost'             => round($avgUnitCost, 2),
                'oldest_price'              => round($oldestPrice, 2),
                'newest_price'              => round($newestPrice, 2),
                'price_change'              => round($priceChange, 2),
                'price_change_percentage'   => round($priceChangePct, 2),
                'purchase_count'            => $purchaseCount,
                'trend_color'               => $trendColor,
                'trend_key'                 => $trendKey,
                'efficiency_key'            => $effKey,
                'efficiency_color'          => $effColor,
                'frequency_key'             => $freqKey,
                'frequency_color'           => $freqColor,
                'frequency_per_month'       => round($freqPerMonth, 2),
            ];
        })->sortByDesc('total_cost')->values();

        // ─── Summary ────────────────────────────────────────────
        $totalProducts    = $productAnalysis->count();
        $withIncrease     = $productAnalysis->where('price_change_percentage', '>', 0)->count();
        $withDecrease     = $productAnalysis->where('price_change_percentage', '<', 0)->count();

        $summary = [
            'total_products'                => $totalProducts,
            'total_quantity'                => round($productAnalysis->sum('total_quantity'), 2),
            'total_cost'                    => round($productAnalysis->sum('total_cost'), 2),
            'avg_price_increase'            => round($productAnalysis->avg('price_change_percentage') ?? 0, 2),
            'products_with_price_increase'  => $withIncrease,
            'products_with_price_decrease'  => $withDecrease,
            'products_stable'               => $totalProducts - $withIncrease - $withDecrease,
        ];

        // ─── Price trend by date ────────────────────────────────
        $priceTrends = $allItems->groupBy(fn($i) => $i->created_at->format('Y-m-d'))
            ->map(function ($items, $date) {
                $qty  = (float) $items->sum('quantity_received');
                $cost = (float) $items->sum('total_cost');
                return [
                    'date'         => $date,
                    'avg_unit_cost'=> round($qty > 0 ? $cost / $qty : 0, 2),
                    'total_quantity'=> round($qty, 2),
                    'total_cost'   => round($cost, 2),
                ];
            })
            ->sortKeys()
            ->values();

        // ─── Top 10 by spend ────────────────────────────────────
        $topProducts = $productAnalysis->take(10)->values();

        // ─── Paginate ───────────────────────────────────────────
        $total    = $productAnalysis->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;
        $pageItems = $productAnalysis->slice($offset, $perPage)->values();

        // Page-footer totals (sum of current page)
        $pageTotals = [
            'quantity' => round($pageItems->sum('total_quantity'), 2),
            'cost'     => round($pageItems->sum('total_cost'), 2),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'summary'      => $summary,
                'price_trends' => $priceTrends,
                'top_products' => $topProducts,
                'products'     => $pageItems,
                'page_totals'  => $pageTotals,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'from'         => $total > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $total),
                    'per_page'     => $perPage,
                ],
                'meta' => [
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                ],
            ],
        ]);
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