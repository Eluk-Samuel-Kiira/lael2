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
use App\Services\PricingResolverService;

class InventoryReportsController extends Controller
{
    public function __construct(
        protected PricingResolverService $pricingResolver
    ) {}

    /**
     * Get current tenant ID
     */
    private function getTenantId()
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('inventory reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('inventory_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
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
     * Resolve the true per-unit cost of a variant.
     * Cascade: grand_total → components → 0
     */
    private function variantCost($variant): float
    {
        if (! $variant) return 0.0;

        $grand = (float) ($variant->grand_total_cost_price ?? 0);
        if ($grand > 0) return $grand;

        $recomputed = (float) ($variant->supplier_cost_price    ?? 0)
                    + (float) ($variant->total_shipping_cost    ?? 0)
                    + (float) ($variant->ura_taxes_applied      ?? 0)
                    + (float) ($variant->additional_expenses    ?? 0);

        return $recomputed;
    }

    /**
     * Selling price actually charged — variant only.
     * (Per-scope overrides are resolved via resolveScopeSellingPrice().)
     */
    private function variantSellingPrice($variant): float
    {
        if (! $variant) return 0.0;
        return (float) ($variant->selling_price ?? 0);
    }

    /**
     * Resolve the selling price for a variant at a specific scope.
     * Cascade:
     *   1. InventoryItems row (has_custom_pricing = 1) at this location/department
     *   2. Variant selling_price
     */
    private function resolveScopeSellingPrice(
        $variant,
        $locationId = null,
        $departmentId = null,
        $inventoryPricing = null
    ): float {
        if (! $variant) return 0.0;

        if ($inventoryPricing && $locationId && $departmentId) {
            $key = "{$variant->id}|{$locationId}|{$departmentId}";
            $row = $inventoryPricing->get($key);
            if ($row && (float) $row->selling_price > 0) {
                return (float) $row->selling_price;
            }
        }

        return (float) ($variant->selling_price ?? 0);
    }

    
    public function summary(Request $request)
    {
        $tenantId = $this->getTenantId();

        $startDate    = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate      = $request->get('end_date', now()->format('Y-m-d'));
        $departmentId = $request->get('department_id');
        $locationId   = $request->get('location_id');
        $variantId    = $request->get('variant_id');
        $isActive     = $request->get('is_active', 'all');
        $perPage      = (int) $request->get('per_page', 15);

        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'product_id']);

        $departments = Department::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $locations   = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);

        return view('reports.inventory.summary', compact(
            'variants', 'departments', 'locations',
            'startDate', 'endDate', 'departmentId', 'locationId', 'variantId', 'isActive', 'perPage'
        ));
    }

    public function summaryData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('inventory reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('inventory_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate    = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate      = $request->get('end_date', now()->format('Y-m-d'));
        $departmentId = $request->get('department_id');
        $locationId   = $request->get('location_id');
        $variantId    = $request->get('variant_id');
        $isActive     = $request->get('is_active', 'all');
        $perPage      = (int) $request->get('per_page', 15);
        $page         = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $query = InventoryItems::with([
                'variant:id,name,sku,barcode,image_url,product_id,grand_total_cost_price,supplier_cost_price,total_shipping_cost,ura_taxes_applied,additional_expenses',
                'variant.product:id,name',
                'departmentItem:id,name',
                'itemLocation:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($departmentId) $query->where('department_id', $departmentId);
        if ($locationId)   $query->where('location_id', $locationId);
        if ($variantId)    $query->where('variant_id', $variantId);
        if ($isActive !== 'all') {
            $query->whereHas('variant', fn($q) => $q->where('is_active', $isActive === '1' || $isActive === 1));
        }

        $allItems = $query->get();

        // ─── Summary (all items, not just page) ──────────────────
        $totalValue = $allItems->sum(function ($item) {
            $v = $item->variant;
            if (!$v) return 0;
            $grand = (float) ($v->grand_total_cost_price ?? 0);
            if ($grand <= 0) {
                $grand = (float) ($v->supplier_cost_price ?? 0)
                    + (float) ($v->total_shipping_cost ?? 0)
                    + (float) ($v->ura_taxes_applied ?? 0)
                    + (float) ($v->additional_expenses ?? 0);
            }
            return (float) ($item->quantity_allocated ?? 0) * $grand;
        });

        $summary = [
            'total_items'              => $allItems->count(),
            'total_quantity_on_hand'   => round($allItems->sum('quantity_allocated'), 2),
            'total_quantity_allocated' => round($allItems->sum('quantity_allocated'), 2),
            'total_value'              => round($totalValue, 2),
            'average_stock_level'      => $allItems->count() > 0 ? round($allItems->avg('quantity_allocated'), 2) : 0,
            'items_below_reorder'      => $allItems->filter(fn($i) =>
                (float) $i->quantity_allocated < (float) ($i->reorder_point ?? 0)
            )->count(),
            'items_above_preferred'    => $allItems->filter(fn($i) =>
                (float) $i->preferred_stock_level > 0
                && (float) $i->quantity_allocated > (float) $i->preferred_stock_level
            )->count(),
            'out_of_stock'             => $allItems->where('quantity_allocated', 0)->count(),
            'total_reorder_point'      => round($allItems->sum('reorder_point'), 2),
            'total_preferred_stock'    => round($allItems->sum('preferred_stock_level'), 2),
        ];

        // ─── Row payload (current page) ──────────────────────────
        $total = $allItems->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;
        $pageItems = $allItems->slice($offset, $perPage)->values();

        $rows = $pageItems->map(function ($item) {
            $v = $item->variant;
            $qoh = (float) ($item->quantity_allocated ?? 0);
            $reorder = (float) ($item->reorder_point ?? 0);
            $preferred = (float) ($item->preferred_stock_level ?? 0);

            $statusColor = 'success';
            $statusText  = __('pagination.in_stock');
            if ($qoh == 0) {
                $statusColor = 'danger';
                $statusText  = __('pagination.out_of_stock');
            } elseif ($reorder > 0 && $qoh <= $reorder) {
                $statusColor = 'warning';
                $statusText  = __('pagination.low_stock');
            } elseif ($preferred > 0 && $qoh > $preferred) {
                $statusColor = 'info';
                $statusText  = __('pagination.over_stock');
            }

            return (object) [
                'id'                 => $item->id,
                'sku'                => $v->sku ?? '-',
                'barcode'            => $v->barcode ?? null,
                'variant_name'       => $v->name ?? '-',
                'product_name'       => $v->product->name ?? null,
                'image_url'          => $v->image_url ?? null,
                'department_name'    => $item->departmentItem->name ?? '-',
                'location_name'      => $item->itemLocation->name ?? '-',
                'quantity_on_hand'   => $qoh,
                'quantity_allocated' => (float) ($item->quantity_allocated ?? 0),
                'reorder_point'      => $reorder,
                'preferred_stock'    => $preferred,
                'status_color'       => $statusColor,
                'status_text'        => $statusText,
                'stock_ratio'        => $reorder > 0 ? min(1, $qoh / $reorder) : null,
                'last_updated'       => $item->updated_at?->format('Y-m-d H:i') ?? '-',
            ];
        });

        // ─── Chart data (from ALL items, not just page) ──────────
        $stockDist = $allItems->take(20)->map(fn($i) => [
            'name'  => $i->variant->name ?? '-',
            'value' => (float) ($i->quantity_allocated ?? 0),
        ])->values();

        $deptDist = $allItems->groupBy(fn($i) => $i->departmentItem->name ?? 'Unspecified')
            ->map(fn($items, $name) => ['name' => $name, 'value' => $items->count()])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'summary'      => $summary,
                'items'        => $rows,
                'stock_dist'   => $stockDist,
                'dept_dist'    => $deptDist,
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
     * Helper — inventory value from query builder
     */
    private function calculateInventoryValue($query)
    {
        return $query->with(['variant'])
            ->get()
            ->sum(function ($item) {
                return (float) ($item->quantity_allocated ?? 0)
                    * $this->variantCost($item->variant);
            });
    }

    /**
     * Helper — inventory value from collection
     */
    private function calculateInventoryValueFromCollection($items)
    {
        return $items->sum(function ($item) {
            return (float) ($item->quantity_allocated ?? 0)
                * $this->variantCost($item->variant);
        });
    }
            
        
    /**
     * Inventory Turnover Report — respects location & department filters
     */
    public function turnover(Request $request)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = tenant_is_single_shop($tenantId);

        $startDate    = $request->get('start_date', now()->subMonths(6)->format('Y-m-d'));
        $endDate      = $request->get('end_date', now()->format('Y-m-d'));
        $variantId    = $request->get('variant_id');
        $departmentId = $request->get('department_id');
        $locationId   = $request->get('location_id');
        $perPage      = (int) $request->get('per_page', 15);

        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)->with(['product:id,name'])
            ->orderBy('name')->get(['id', 'name', 'sku', 'product_id']);

        $departments = Department::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $locations   = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);

        return view('reports.inventory.turnover', compact(
            'variants', 'departments', 'locations',
            'startDate', 'endDate', 'variantId', 'departmentId', 'locationId', 'perPage', 'isSingleShop'
        ));
    }

    public function turnoverData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('inventory reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('inventory_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId     = $user->tenant_id;
        $isSingleShop = tenant_is_single_shop($tenantId);

        $startDate    = $request->get('start_date', now()->subMonths(6)->format('Y-m-d'));
        $endDate      = $request->get('end_date', now()->format('Y-m-d'));
        $variantId    = $request->get('variant_id');
        $departmentId = $request->get('department_id');
        $locationId   = $request->get('location_id');
        $perPage      = (int) $request->get('per_page', 15);
        $page         = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $daysInPeriod = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;

        // ─── Scope guard: variants that actually have inventory in scope ──
        $variantIdsInScope = null;
        if ($locationId || $departmentId) {
            $scopeQuery = InventoryItems::query()->where('tenant_id', $tenantId);
            if ($locationId)   $scopeQuery->where('location_id',   $locationId);
            if ($departmentId) $scopeQuery->where('department_id', $departmentId);
            $variantIdsInScope = $scopeQuery->pluck('variant_id')->unique()->filter()->values();
        }

        // ─── Variant base query ───────────────────────────────────────────
        $variantsQuery = ProductVariant::with(['product:id,name'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);

        if ($variantId)            $variantsQuery->where('id', $variantId);
        if ($variantIdsInScope !== null) $variantsQuery->whereIn('id', $variantIdsInScope);

        $allVariants = $variantsQuery->get();

        // ─── Movement data ────────────────────────────────────────────────
        $movementData = collect();

        if ($isSingleShop) {
            $logs = SingleShopInventoryLog::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59',
                ])
                ->when($variantIdsInScope !== null, fn($q) => $q->whereIn('variant_id', $variantIdsInScope))
                ->get();

            $logsByVariant = $logs->groupBy('variant_id');

            foreach ($allVariants as $variant) {
                $variantLogs = $logsByVariant->get($variant->id, collect());
                if ($variantLogs->isEmpty()) continue;

                $movementData->push([
                    'variant'           => $variant,
                    'total_movement'    => $variantLogs->sum(fn($l) => abs($l->quantity_change)),
                    'avg_stock_level'   => $variantLogs->avg('quantity_before') ?: 1,
                    'transaction_count' => $variantLogs->count(),
                    'first_movement'    => $variantLogs->min('created_at'),
                    'last_movement'     => $variantLogs->max('created_at'),
                ]);
            }
        } else {
            $transactionsQuery = InventoryTransactions::with([
                    'InventoryItems' => function ($q) use ($departmentId, $locationId) {
                        if ($departmentId) $q->where('department_id', $departmentId);
                        if ($locationId)   $q->where('location_id',   $locationId);
                    },
                ])
                ->where('tenant_id', $tenantId)
                ->where('type', 'sale')
                ->whereBetween('created_at', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59',
                ]);

            if ($departmentId) $transactionsQuery->whereHas('InventoryItems', fn($q) => $q->where('department_id', $departmentId));
            if ($locationId)   $transactionsQuery->whereHas('InventoryItems', fn($q) => $q->where('location_id', $locationId));
            if ($variantIdsInScope !== null) {
                $transactionsQuery->whereHas('InventoryItems', fn($q) => $q->whereIn('variant_id', $variantIdsInScope));
            }

            $transactions = $transactionsQuery->get();
            $transactionsByVariant = $transactions->groupBy(fn($t) => $t->InventoryItems->variant_id ?? null);

            foreach ($allVariants as $variant) {
                $variantTransactions = $transactionsByVariant->get($variant->id, collect());
                if ($variantTransactions->isEmpty()) continue;

                $inventoryItemQuery = InventoryItems::where('variant_id', $variant->id)->where('tenant_id', $tenantId);
                if ($departmentId) $inventoryItemQuery->where('department_id', $departmentId);
                if ($locationId)   $inventoryItemQuery->where('location_id',   $locationId);

                $movementData->push([
                    'variant'           => $variant,
                    'total_movement'    => $variantTransactions->sum(fn($t) => abs($t->quantity)),
                    'avg_stock_level'   => $inventoryItemQuery->avg('quantity_allocated') ?: 1,
                    'transaction_count' => $variantTransactions->count(),
                    'first_movement'    => $variantTransactions->min('created_at'),
                    'last_movement'     => $variantTransactions->max('created_at'),
                ]);
            }
        }

        // ─── Turnover metrics ─────────────────────────────────────────────
        $turnoverCollection = $movementData->map(function ($data) use ($daysInPeriod) {
            $variant          = $data['variant'];
            $totalMovement    = $data['total_movement'];
            $avgStockLevel    = $data['avg_stock_level'] ?: 1;
            $transactionCount = $data['transaction_count'];

            $turnoverRate      = $avgStockLevel > 0 ? $totalMovement / $avgStockLevel : 0;
            $daysInventoryHeld = $turnoverRate > 0 ? $daysInPeriod / $turnoverRate : $daysInPeriod;

            if ($turnoverRate >= 10) {
                $movementCategory = 'fast';
                $movementLabel    = __('pagination.fast_moving');
                $movementColor    = 'danger';
            } elseif ($turnoverRate >= 1) {
                $movementCategory = 'slow';
                $movementLabel    = __('pagination.slow_moving');
                $movementColor    = 'warning';
            } else {
                $movementCategory = 'non';
                $movementLabel    = __('pagination.non_moving');
                $movementColor    = 'dark';
            }

            return (object) [
                'variant_id'          => $variant->id,
                'variant_name'        => $variant->name,
                'variant_sku'         => $variant->sku,
                'variant_barcode'     => $variant->barcode ?? null,
                'variant_image'       => $variant->image_url ?? null,
                'product_name'        => $variant->product->name ?? '',
                'total_movement'      => round($totalMovement, 2),
                'avg_stock_level'     => round($avgStockLevel, 2),
                'transaction_count'   => $transactionCount,
                'turnover_rate'       => round($turnoverRate, 2),
                'days_inventory_held' => round($daysInventoryHeld, 2),
                'movement_category'   => $movementCategory,
                'movement_label'      => $movementLabel,
                'movement_color'      => $movementColor,
            ];
        });

        $sortedTurnover = $turnoverCollection->sortByDesc('turnover_rate')->values();

        $summary = [
            'avg_turnover_rate'  => round($sortedTurnover->avg('turnover_rate') ?? 0, 2),
            'avg_days_held'      => round($sortedTurnover->avg('days_inventory_held') ?? 0, 1),
            'total_movement'     => round($sortedTurnover->sum('total_movement'), 2),
            'total_transactions' => $sortedTurnover->sum('transaction_count'),
            'fast_moving'        => $sortedTurnover->where('turnover_rate', '>=', 10)->count(),
            'slow_moving'        => $sortedTurnover->filter(fn($r) => $r->turnover_rate >= 1 && $r->turnover_rate < 10)->count(),
            'non_moving'         => $sortedTurnover->where('turnover_rate', '<', 1)->count(),
            'total_items'        => $sortedTurnover->count(),
        ];

        // ─── Paginate ─────────────────────────────────────────────────────
        $totalRows = $sortedTurnover->count();
        $lastPage  = max(1, (int) ceil($totalRows / $perPage));
        $page      = min($page, $lastPage);
        $offset    = ($page - 1) * $perPage;
        $paginated = $sortedTurnover->slice($offset, $perPage)->values();

        // ─── Chart data ───────────────────────────────────────────────────
        $topTurnover = $sortedTurnover->take(20)->map(fn($r) => [
            'name'  => mb_strimwidth($r->variant_name, 0, 20, '…'),
            'value' => (float) $r->turnover_rate,
        ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'summary'      => $summary,
                'items'        => $paginated,
                'top_turnover' => $topTurnover,
                'categories'   => [
                    ['name' => __('pagination.fast_moving'), 'value' => $summary['fast_moving']],
                    ['name' => __('pagination.slow_moving'), 'value' => $summary['slow_moving']],
                    ['name' => __('pagination.non_moving'),  'value' => $summary['non_moving']],
                ],
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $totalRows,
                    'from'         => $totalRows > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $totalRows),
                    'per_page'     => $perPage,
                ],
                'meta' => [
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'days_in_period'=> $daysInPeriod,
                ],
            ],
        ]);
    }


    /**
     * ✅ FIXED: Stock Aging Report with Proper Prices and Batch Tracking
     */
    public function stockAging(Request $request)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = tenant_is_single_shop($tenantId);

        $departmentId = $request->get('department_id');
        $locationId   = $request->get('location_id');
        $variantId    = $request->get('variant_id');
        $category     = $request->get('category');
        $perPage      = (int) $request->get('per_page', 15);

        $departments = Department::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $locations   = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $variants    = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)->with(['product:id,name'])
            ->orderBy('name')->get(['id', 'name', 'sku', 'product_id']);

        return view('reports.inventory.stock-aging', compact(
            'departments', 'locations', 'variants',
            'departmentId', 'locationId', 'variantId', 'category', 'perPage', 'isSingleShop'
        ));
    }

    public function stockAgingData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('inventory reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('inventory_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId     = $user->tenant_id;
        $isSingleShop = tenant_is_single_shop($tenantId);

        $departmentId = $request->get('department_id');
        $locationId   = $request->get('location_id');
        $variantId    = $request->get('variant_id');
        $category     = $request->get('category');
        $perPage      = (int) $request->get('per_page', 15);
        $page         = max(1, (int) $request->get('page', 1));

        // ─── Items ─────────────────────────────────────────────────
        $allItems = InventoryItems::with([
                'variant.product:id,name',
                'variant:id,name,sku,barcode,image_url,product_id,grand_total_cost_price,supplier_cost_price,total_shipping_cost,ura_taxes_applied,additional_expenses,selling_price',
                'departmentItem:id,name',
                'itemLocation:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->where('quantity_allocated', '>', 0)
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->when($locationId,   fn($q) => $q->where('location_id',   $locationId))
            ->when($variantId,    fn($q) => $q->where('variant_id',    $variantId))
            ->get();

        // ─── Batch logs (for last-event display) ──────────────────
        $variantIds = $allItems->pluck('variant_id')->filter()->unique();
        $batchLogs = BatchLog::where('tenant_id', $tenantId)
            ->whereIn('variant_id', $variantIds)
            ->orderByDesc('event_date')
            ->get()
            ->groupBy('variant_id');

        // ─── Batch expiry fallback ─────────────────────────────────
        $batchNumbers = $allItems->pluck('batch_number')->filter()->values();
        $batchItems = collect();
        if ($batchNumbers->isNotEmpty()) {
            $batchItems = PurchaseReceiptItem::with(['purchaseReceipt.purchaseOrder'])
                ->whereIn('batch_number', $batchNumbers)
                ->get()
                ->filter(fn($item) =>
                    $item->purchaseReceipt?->purchaseOrder?->tenant_id == $tenantId
                )
                ->groupBy('batch_number');
        }

        // ─── Aging computation ─────────────────────────────────────
        $agingCollection = collect();
        $agingCategories = [
            'expired' => 0.0, '1_week' => 0.0, '1_month' => 0.0,
            '3_months' => 0.0, '6_months' => 0.0, 'over_6_months' => 0.0,
        ];
        $today = now();
        $totalValueAtRisk = 0.0;
        $totalInventoryValue = 0.0;

        foreach ($allItems as $item) {
            $variant = $item->variant;
            if (!$variant) continue;

            $costPrice    = $this->variantCost($variant);
            $sellingPrice = $this->variantSellingPrice($variant);
            $daysSinceLastMovement = $this->getDaysSinceLastMovement($item, $isSingleShop);

            // Expiry
            $expiryDate = null;
            $batchNumber = $item->batch_number;
            if ($item->expiry_date) {
                $expiryDate = Carbon::parse($item->expiry_date);
            } elseif ($batchNumber && $batchItems->has($batchNumber)) {
                $batchInfo = $batchItems->get($batchNumber)->first();
                if ($batchInfo?->expiry_date) $expiryDate = Carbon::parse($batchInfo->expiry_date);
            }
            $daysToExpiry = $expiryDate ? $today->diffInDays($expiryDate, false) : null;

            // Last batch log
            $variantBatchLogs = $batchLogs->get($item->variant_id, collect());
            $lastBatchLog = $variantBatchLogs->first();

            // Category + status
            if ($expiryDate && $daysToExpiry !== null) {
                if ($daysToExpiry < 0) {
                    [$categoryKey, $statusColor, $statusText, $urgency] = ['expired', 'danger', __('pagination.expired'), 'immediate'];
                } elseif ($daysToExpiry <= 7) {
                    [$categoryKey, $statusColor, $statusText, $urgency] = ['1_week', 'warning', __('pagination.expires_in_1_week'), 'critical'];
                } elseif ($daysToExpiry <= 30) {
                    [$categoryKey, $statusColor, $statusText, $urgency] = ['1_month', 'warning', __('pagination.expires_in_1_month'), 'high'];
                } elseif ($daysToExpiry <= 90) {
                    [$categoryKey, $statusColor, $statusText, $urgency] = ['3_months', 'info', __('pagination.expires_in_3_months'), 'medium'];
                } elseif ($daysToExpiry <= 180) {
                    [$categoryKey, $statusColor, $statusText, $urgency] = ['6_months', 'success', __('pagination.expires_in_6_months'), 'low'];
                } else {
                    [$categoryKey, $statusColor, $statusText, $urgency] = ['over_6_months', 'primary', __('pagination.expires_after_6_months'), 'none'];
                }
            } else {
                if ($daysSinceLastMovement > 180) {
                    [$categoryKey, $statusColor, $statusText, $urgency] = ['over_6_months', 'warning', __('pagination.no_movement_6_months'), 'medium'];
                } elseif ($daysSinceLastMovement > 90) {
                    [$categoryKey, $statusColor, $statusText, $urgency] = ['3_months', 'info', __('pagination.no_movement_3_months'), 'low'];
                } else {
                    [$categoryKey, $statusColor, $statusText, $urgency] = ['6_months', 'success', __('pagination.active'), 'none'];
                }
            }

            $quantity = (float) $item->quantity_allocated;
            $agingCategories[$categoryKey] += $quantity;

            if ($category && $category !== $categoryKey) continue;

            $inventoryValue = $quantity * $costPrice;
            $totalInventoryValue += $inventoryValue;
            $valueAtRisk = in_array($categoryKey, ['expired', '1_week', '1_month']) ? $inventoryValue : 0;
            if ($valueAtRisk > 0) $totalValueAtRisk += $valueAtRisk;

            $agingCollection->push((object) [
                'id'                  => $item->id,
                'sku'                 => $variant->sku ?? '-',
                'barcode'             => $variant->barcode ?? null,
                'variant_name'        => $variant->name,
                'product_name'        => $variant->product->name ?? '',
                'image_url'           => $variant->image_url ?? null,
                'department_name'     => $item->departmentItem->name ?? '-',
                'location_name'       => $item->itemLocation->name ?? '-',
                'batch_number'        => $batchNumber,
                'is_batch_tracked'    => !empty($batchNumber),
                'expiry_date'         => $expiryDate?->format('Y-m-d'),
                'days_to_expiry'      => $daysToExpiry ?? $daysSinceLastMovement,
                'days_since_last_movement' => $daysSinceLastMovement,
                'quantity_allocated'  => $quantity,
                'cost_price'          => round($costPrice, 2),
                'selling_price'       => round($sellingPrice, 2),
                'inventory_value'     => round($inventoryValue, 2),
                'value_at_risk'       => round($valueAtRisk, 2),
                'profit_margin'       => $costPrice > 0 ? round((($sellingPrice - $costPrice) / $costPrice) * 100, 2) : 0,
                'category_key'        => $categoryKey,
                'status_color'        => $statusColor,
                'status_text'         => $statusText,
                'urgency'             => $urgency,
                'last_batch_event'    => $lastBatchLog?->event_date?->format('Y-m-d'),
                'last_batch_event_type' => $lastBatchLog?->type,
                'last_batch_quantity' => $lastBatchLog?->quantity_after,
            ]);
        }

        // ─── Sort by urgency ───────────────────────────────────────
        $urgencyOrder = ['immediate' => 1, 'critical' => 2, 'high' => 3, 'medium' => 4, 'low' => 5, 'none' => 6];
        $sortedAging = $agingCollection->sortBy(fn($i) => $urgencyOrder[$i->urgency] ?? 99)->values();

        // ─── Summary ───────────────────────────────────────────────
        $summary = [
            'expired'            => round($agingCategories['expired'], 2),
            '1_week'             => round($agingCategories['1_week'], 2),
            '1_month'            => round($agingCategories['1_month'], 2),
            '3_months'           => round($agingCategories['3_months'], 2),
            '6_months'           => round($agingCategories['6_months'], 2),
            'over_6_months'      => round($agingCategories['over_6_months'], 2),
            'total_items'        => $sortedAging->count(),
            'total_value_at_risk'=> round($totalValueAtRisk, 2),
            'total_inventory_value' => round($totalInventoryValue, 2),
            'batch_tracked_items'=> $sortedAging->where('is_batch_tracked', true)->count(),
            'expired_value'      => round($sortedAging->where('category_key', 'expired')->sum('inventory_value'), 2),
            'avg_profit_margin'  => round($sortedAging->avg('profit_margin') ?? 0, 2),
        ];

        // ─── Paginate ──────────────────────────────────────────────
        $totalRows = $sortedAging->count();
        $lastPage  = max(1, (int) ceil($totalRows / $perPage));
        $page      = min($page, $lastPage);
        $offset    = ($page - 1) * $perPage;
        $paginated = $sortedAging->slice($offset, $perPage)->values();

        // ─── Chart data ────────────────────────────────────────────
        $distChart = $sortedAging->take(20)->map(fn($r) => [
            'name'      => mb_strimwidth($r->variant_name, 0, 20, '…'),
            'days'      => (float) $r->days_to_expiry,
            'quantity'  => (float) $r->quantity_allocated,
        ])->values();

        $catChart = [
            ['name' => __('pagination.expired'),        'value' => $summary['expired']],
            ['name' => __('pagination.within_1_week'),  'value' => $summary['1_week']],
            ['name' => __('pagination.within_1_month'), 'value' => $summary['1_month']],
            ['name' => __('pagination.within_3_months'),'value' => $summary['3_months']],
            ['name' => __('pagination.within_6_months'),'value' => $summary['6_months']],
            ['name' => __('pagination.over_6_months'),  'value' => $summary['over_6_months']],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'summary'    => $summary,
                'items'      => $paginated,
                'dist_chart' => $distChart,
                'cat_chart'  => $catChart,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $totalRows,
                    'from'         => $totalRows > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $totalRows),
                    'per_page'     => $perPage,
                ],
            ],
        ]);
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
        $tenantId     = $this->getTenantId();
        $isSingleShop = tenant_is_single_shop($tenantId);

        $departmentId = $request->get('department_id');
        $locationId   = $request->get('location_id');
        $variantId    = $request->get('variant_id');
        $severity     = $request->get('severity', 'all');
        $perPage      = (int) $request->get('per_page', 15);

        $departments = Department::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $locations   = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $variants    = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)->with(['product:id,name'])
            ->orderBy('name')->get(['id', 'name', 'sku', 'product_id']);

        return view('reports.inventory.low-stock-alerts', compact(
            'departments', 'locations', 'variants',
            'departmentId', 'locationId', 'variantId', 'severity', 'perPage', 'isSingleShop'
        ));
    }

    public function lowStockAlertsData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('inventory reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('inventory_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId     = $user->tenant_id;
        $isSingleShop = tenant_is_single_shop($tenantId);

        $departmentId = $request->get('department_id');
        $locationId   = $request->get('location_id');
        $variantId    = $request->get('variant_id');
        $severity     = $request->get('severity', 'all');
        $perPage      = (int) $request->get('per_page', 15);
        $page         = max(1, (int) $request->get('page', 1));

        $allItems = InventoryItems::with([
                'variant.product:id,name',
                'variant:id,name,sku,barcode,image_url,product_id,grand_total_cost_price,supplier_cost_price,total_shipping_cost,ura_taxes_applied,additional_expenses,low_stock_level',
                'departmentItem:id,name',
                'itemLocation:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->when($locationId,   fn($q) => $q->where('location_id',   $locationId))
            ->when($variantId,    fn($q) => $q->where('variant_id',    $variantId))
            ->get();

        $lowStockItems = collect();

        foreach ($allItems as $item) {
            $currentStock   = (float) ($item->quantity_allocated ?? 0);
            $reorderPoint   = (float) ($item->reorder_point ?? 0);
            $preferredStock = (float) ($item->preferred_stock_level ?? 0);

            // Determine threshold
            $threshold = 0.0;
            $hasThreshold = false;
            if ($isSingleShop) {
                $variant = $item->variant;
                if ($variant && (float) ($variant->low_stock_level ?? 0) > 0) {
                    $threshold = (float) $variant->low_stock_level;
                    $hasThreshold = true;
                }
            } else {
                if ($preferredStock > 0) {
                    $threshold = $preferredStock;
                    $hasThreshold = true;
                } elseif ($reorderPoint > 0) {
                    $threshold = $reorderPoint;
                    $hasThreshold = true;
                }
            }

            if (!$hasThreshold) continue;
            if ($currentStock > $threshold) continue;

            // Severity
            if ($currentStock <= $threshold * 0.3) {
                $severityLevel = 'critical';
                $severityColor = 'danger';
                $severityIcon  = 'ki-shield-cross';
                $severityText  = __('pagination.critical');
                $urgencyText   = __('pagination.immediate');
                $urgencyColor  = 'danger';
            } elseif ($currentStock <= $threshold * 0.7) {
                $severityLevel = 'warning';
                $severityColor = 'warning';
                $severityIcon  = 'ki-shield-tick';
                $severityText  = __('pagination.warning');
                $urgencyText   = __('pagination.soon');
                $urgencyColor  = 'warning';
            } else {
                $severityLevel = 'low';
                $severityColor = 'info';
                $severityIcon  = 'ki-information';
                $severityText  = __('pagination.low');
                $urgencyText   = __('pagination.later');
                $urgencyColor  = 'info';
            }

            if ($severity !== 'all' && $severityLevel !== $severity) continue;

            $deficit = $threshold - $currentStock;
            $cost    = $this->variantCost($item->variant);
            $variant = $item->variant;

            $lowStockItems->push((object) [
                'id'                  => $item->id,
                'sku'                 => $variant->sku ?? '-',
                'barcode'             => $variant->barcode ?? null,
                'variant_name'        => $variant->name ?? '-',
                'product_name'        => $variant->product->name ?? '',
                'image_url'           => $variant->image_url ?? null,
                'department_name'     => $item->departmentItem->name ?? '-',
                'location_name'       => $item->itemLocation->name ?? '-',
                'quantity_on_hand'    => $currentStock,
                'reorder_point'       => $reorderPoint,
                'preferred_stock'     => $preferredStock,
                'threshold'           => $threshold,
                'deficit'             => $deficit,
                'deficit_value'       => $deficit * $cost,
                'cost_price'          => round($cost, 2),
                'reorder_percentage'  => $threshold > 0 ? ($currentStock / $threshold) * 100 : 0,
                'severity'            => $severityLevel,
                'severity_color'      => $severityColor,
                'severity_icon'       => $severityIcon,
                'severity_text'       => $severityText,
                'urgency_text'        => $urgencyText,
                'urgency_color'       => $urgencyColor,
                'stock_source'        => $isSingleShop ? __('pagination.global_stock') : __('pagination.department_stock'),
            ]);
        }

        // Sort by severity
        $severityOrder = ['critical' => 1, 'warning' => 2, 'low' => 3];
        $sorted = $lowStockItems->sortBy(fn($i) => $severityOrder[$i->severity] ?? 99)->values();

        // Paginate
        $total    = $sorted->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;
        $paginated = $sorted->slice($offset, $perPage)->values();

        // Summary
        $summary = [
            'critical'            => $sorted->where('severity', 'critical')->count(),
            'warning'             => $sorted->where('severity', 'warning')->count(),
            'low'                 => $sorted->where('severity', 'low')->count(),
            'total_items'         => $sorted->count(),
            'total_value_at_risk' => round($sorted->sum('deficit_value'), 2),
            'is_single_shop'      => $isSingleShop,
        ];

        // Chart: department distribution
        $deptDist = $sorted->groupBy('department_name')
            ->map(fn($items, $name) => ['name' => $name ?: 'Unspecified', 'value' => $items->count()])
            ->sortByDesc('value')
            ->values();

        // Chart: severity distribution
        $severityDist = [
            ['name' => __('pagination.critical'), 'value' => $summary['critical'], 'color' => 'danger'],
            ['name' => __('pagination.warning'),  'value' => $summary['warning'],  'color' => 'warning'],
            ['name' => __('pagination.low'),      'value' => $summary['low'],      'color' => 'info'],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'summary'       => $summary,
                'items'         => $paginated,
                'dept_dist'     => $deptDist,
                'severity_dist' => $severityDist,
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
    
    public function transactions(Request $request)
    {
        $tenantId = $this->getTenantId();

        $startDate    = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate      = $request->get('end_date', now()->format('Y-m-d'));
        $type         = $request->get('type', 'all');
        $departmentId = $request->get('department_id');
        $locationId   = $request->get('location_id');
        $variantId    = $request->get('variant_id');
        $perPage      = (int) $request->get('per_page', 15);

        $departments = Department::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $locations   = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $variants    = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)->with(['product:id,name'])
            ->orderBy('name')->get(['id', 'name', 'sku', 'product_id']);

        return view('reports.inventory.transactions', compact(
            'departments', 'locations', 'variants',
            'startDate', 'endDate', 'type', 'departmentId', 'locationId', 'variantId', 'perPage'
        ));
    }

    public function transactionsData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('inventory reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('inventory_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate    = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate      = $request->get('end_date', now()->format('Y-m-d'));
        $type         = $request->get('type', 'all');
        $departmentId = $request->get('department_id');
        $locationId   = $request->get('location_id');
        $variantId    = $request->get('variant_id');
        $perPage      = (int) $request->get('per_page', 15);
        $page         = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $query = InventoryTransactions::with([
                'InventoryItems.variant:id,name,sku,image_url,product_id',
                'InventoryItems.variant.product:id,name',
                'InventoryItems.departmentItem:id,name',
                'InventoryItems.itemLocation:id,name',
                'createdBy:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($type !== 'all') $query->where('type', $type);
        if ($departmentId) $query->whereHas('InventoryItems', fn($q) => $q->where('department_id', $departmentId));
        if ($locationId)   $query->whereHas('InventoryItems', fn($q) => $q->where('location_id', $locationId));
        if ($variantId)    $query->whereHas('InventoryItems', fn($q) => $q->where('variant_id', $variantId));

        $allTransactions = $query->get();

        // ─── Type summary ────────────────────────────────────────
        $typeBuckets = $allTransactions->groupBy('type')->map(function ($items, $typeKey) {
            return [
                'type'           => $typeKey,
                'count'          => $items->count(),
                'total_quantity' => (float) $items->sum('quantity'),
                'avg_quantity'   => $items->count() > 0 ? (float) $items->avg('quantity') : 0,
            ];
        })->values();

        // ─── Summary totals ──────────────────────────────────────
        $positive = (float) $allTransactions->where('quantity', '>', 0)->sum('quantity');
        $negative = abs((float) $allTransactions->where('quantity', '<', 0)->sum('quantity'));
        $netChange = $positive - $negative;
        $daysInRange = max(1, Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1);

        $summary = [
            'total_transactions'   => $allTransactions->count(),
            'total_quantity_moved' => round(abs($allTransactions->sum('quantity')), 2),
            'positive_movements'   => round($positive, 2),
            'negative_movements'   => round($negative, 2),
            'net_change'           => round($netChange, 2),
            'avg_daily'            => round($allTransactions->count() / $daysInRange, 2),
        ];

        // ─── Daily trend ─────────────────────────────────────────
        $dailyBuckets = [];
        foreach ($allTransactions as $t) {
            $d = $t->created_at?->format('Y-m-d');
            if (!$d) continue;
            $dailyBuckets[$d] ??= ['count' => 0, 'quantity' => 0.0];
            $dailyBuckets[$d]['count']++;
            $dailyBuckets[$d]['quantity'] += (float) $t->quantity;
        }
        ksort($dailyBuckets);
        $dailyTrend = [];
        foreach ($dailyBuckets as $date => $b) {
            $dailyTrend[] = [
                'date'     => $date,
                'count'    => $b['count'],
                'quantity' => round($b['quantity'], 2),
            ];
        }

        // ─── Pagination ──────────────────────────────────────────
        $total    = $allTransactions->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;
        $paginated = $allTransactions->sortByDesc('created_at')->slice($offset, $perPage)->values();

        $rows = $paginated->map(function ($t) {
            $item    = $t->InventoryItems;
            $variant = $item?->variant;

            return (object) [
                'id'                 => $t->id,
                'created_at'         => $t->created_at?->format('Y-m-d H:i:s'),
                'type'               => $t->type,
                'type_label'         => ucfirst(str_replace('_', ' ', $t->type)),
                'quantity'           => (float) $t->quantity,
                'sku'                => $variant?->sku ?? '-',
                'variant_name'       => $variant?->name ?? '-',
                'product_name'       => $variant?->product?->name ?? '',
                'image_url'          => $variant?->image_url,
                'department_name'    => $item?->departmentItem?->name ?? '-',
                'location_name'      => $item?->itemLocation?->name ?? '-',
                'reference_id'       => $t->reference_id,
                'reference_type'     => $t->reference_type,
                'notes'              => $t->notes,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'summary'      => $summary,
                'type_summary' => $typeBuckets,
                'daily_trend'  => $dailyTrend,
                'items'        => $rows,
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

    public function transactionDetail($id)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('inventory reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        $tenantId = $user->tenant_id;

        $t = InventoryTransactions::with([
                'InventoryItems.variant.product:id,name',
                'InventoryItems.departmentItem:id,name',
                'InventoryItems.itemLocation:id,name',
                'createdBy:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->firstOrFail();

        $item    = $t->InventoryItems;
        $variant = $item?->variant;

        return response()->json([
            'success' => true,
            'data' => [
                'id'                   => $t->id,
                'created_at'           => $t->created_at?->format('Y-m-d H:i:s'),
                'type'                 => $t->type,
                'type_label'           => ucfirst(str_replace('_', ' ', $t->type)),
                'quantity'             => (float) $t->quantity,
                'notes'                => $t->notes,
                'reference_id'         => $t->reference_id,
                'reference_type'       => $t->reference_type,
                'reference_label'      => $t->reference_type ? strtoupper(str_replace('_', ' ', $t->reference_type)) . ' #' . $t->reference_id : null,
                'unit_cost_price'      => (float) ($t->unit_cost_price ?? 0),
                'unit_selling_price'   => (float) ($t->unit_selling_price ?? 0),
                'total_cost_value'     => (float) ($t->total_cost_value ?? 0),
                'total_selling_value'  => (float) ($t->total_selling_value ?? 0),
                'gross_profit'         => (float) (($t->total_selling_value ?? 0) - ($t->total_cost_value ?? 0)),
                'created_by'           => $t->createdBy?->name ?? 'System',
                'variant' => [
                    'name'         => $variant?->name ?? '-',
                    'sku'          => $variant?->sku ?? '-',
                    'image_url'    => $variant?->image_url,
                    'product_name' => $variant?->product?->name ?? '',
                ],
                'department' => $item?->departmentItem?->name ?? '-',
                'location'   => $item?->itemLocation?->name ?? '-',
            ],
        ]);
    }

    
    /**
     * Inventory Adjustments Report with Pure Eloquent
     */
    public function adjustments(Request $request)
    {
        $tenantId = $this->getTenantId();

        $startDate    = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate      = $request->get('end_date', now()->format('Y-m-d'));
        $departmentId = $request->get('department_id');
        $locationId   = $request->get('location_id');
        $variantId    = $request->get('variant_id');
        $perPage      = (int) $request->get('per_page', 15);

        $departments = Department::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $locations   = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $variants    = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)->with(['product:id,name'])
            ->orderBy('name')->get(['id', 'name', 'sku', 'product_id']);

        return view('reports.inventory.adjustments', compact(
            'departments', 'locations', 'variants',
            'startDate', 'endDate', 'departmentId', 'locationId', 'variantId', 'perPage'
        ));
    }

    public function adjustmentsData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('inventory reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('inventory_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate    = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate      = $request->get('end_date', now()->format('Y-m-d'));
        $departmentId = $request->get('department_id');
        $locationId   = $request->get('location_id');
        $variantId    = $request->get('variant_id');
        $perPage      = (int) $request->get('per_page', 15);
        $page         = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $query = InventoryAdjustments::with([
                'inventoryItems.variant:id,name,sku,image_url,product_id',
                'inventoryItems.variant.product:id,name',
                'inventoryItems.departmentItem:id,name',
                'inventoryItems.itemLocation:id,name',
                'createdBy:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($departmentId) {
            $query->whereHas('inventoryItems', fn($q) => $q->where('department_id', $departmentId));
        }
        if ($locationId) {
            $query->whereHas('inventoryItems', fn($q) => $q->where('location_id', $locationId));
        }
        if ($variantId) {
            $query->whereHas('inventoryItems', fn($q) => $q->where('variant_id', $variantId));
        }

        $allAdjustments = $query->get();

        // ─── Summary ──────────────────────────────────────────────
        $summary = [
            'total_adjustments'      => $allAdjustments->count(),
            'total_quantity_changed' => round($allAdjustments->sum(fn($i) => abs($i->quantity_after - $i->quantity_before)), 2),
            'net_change'             => round($allAdjustments->sum(fn($i) => $i->quantity_after - $i->quantity_before), 2),
            'increase_count'         => $allAdjustments->filter(fn($i) => $i->quantity_after > $i->quantity_before)->count(),
            'decrease_count'         => $allAdjustments->filter(fn($i) => $i->quantity_after < $i->quantity_before)->count(),
            'total_before'           => round($allAdjustments->sum('quantity_before'), 2),
            'total_after'            => round($allAdjustments->sum('quantity_after'), 2),
            'total_value_change'     => round($allAdjustments->sum('total_value_change'), 2),
        ];

        // ─── Paginated rows ──────────────────────────────────────
        $total    = $allAdjustments->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;
        $paginated = $allAdjustments->sortByDesc('created_at')->slice($offset, $perPage)->values();

        $rows = $paginated->map(function ($a) {
            $item    = $a->inventoryItems;
            $variant = $item?->variant;
            $change  = $a->quantity_after - $a->quantity_before;

            return (object) [
                'id'                => $a->id,
                'created_at'        => $a->created_at?->format('Y-m-d H:i:s'),
                'sku'               => $variant?->sku ?? '-',
                'variant_name'      => $variant?->name ?? '-',
                'product_name'      => $variant?->product?->name ?? '',
                'image_url'         => $variant?->image_url,
                'department_name'   => $item?->departmentItem?->name ?? '-',
                'location_name'     => $item?->itemLocation?->name ?? '-',
                'quantity_before'   => (float) $a->quantity_before,
                'quantity_after'    => (float) $a->quantity_after,
                'change'            => round($change, 2),
                'reason'            => $a->reason,
                'reason_label'      => ucfirst(str_replace('_', ' ', $a->reason ?? '')),
                'notes'             => $a->notes,
                'adjusted_by'       => $a->createdBy?->name ?? '-',
                'unit_cost_price'   => (float) ($a->unit_cost_price ?? 0),
                'total_value_change'=> (float) ($a->total_value_change ?? 0),
            ];
        });

        // ─── Chart data ──────────────────────────────────────────
        $typeData = [
            ['name' => __('pagination.increase'),  'value' => $summary['increase_count'],  'color' => 'success'],
            ['name' => __('pagination.decrease'),  'value' => $summary['decrease_count'],  'color' => 'danger'],
            ['name' => __('pagination.no_change'), 'value' => max(0, $summary['total_adjustments'] - $summary['increase_count'] - $summary['decrease_count']), 'color' => 'secondary'],
        ];

        $dailyBuckets = [];
        foreach ($allAdjustments as $a) {
            $d = $a->created_at?->format('Y-m-d');
            if (!$d) continue;
            $dailyBuckets[$d] ??= ['count' => 0, 'net' => 0.0];
            $dailyBuckets[$d]['count']++;
            $dailyBuckets[$d]['net'] += $a->quantity_after - $a->quantity_before;
        }
        ksort($dailyBuckets);
        $dailyTrend = [];
        foreach ($dailyBuckets as $date => $b) {
            $dailyTrend[] = [
                'date'  => $date,
                'count' => $b['count'],
                'net'   => round($b['net'], 2),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'summary'       => $summary,
                'items'         => $rows,
                'type_dist'     => $typeData,
                'daily_trend'   => $dailyTrend,
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

    public function adjustmentDetail($id)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('inventory reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        $tenantId = $user->tenant_id;

        $a = InventoryAdjustments::with([
                'inventoryItems.variant.product:id,name',
                'inventoryItems.departmentItem:id,name',
                'inventoryItems.itemLocation:id,name',
                'createdBy:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->firstOrFail();

        $item    = $a->inventoryItems;
        $variant = $item?->variant;
        $change  = $a->quantity_after - $a->quantity_before;

        return response()->json([
            'success' => true,
            'data' => [
                'id'                => $a->id,
                'created_at'        => $a->created_at?->format('Y-m-d H:i:s'),
                'reason'            => $a->reason,
                'reason_label'      => ucfirst(str_replace('_', ' ', $a->reason ?? '')),
                'notes'             => $a->notes,
                'quantity_before'   => (float) $a->quantity_before,
                'quantity_after'    => (float) $a->quantity_after,
                'change'            => round($change, 2),
                'unit_cost_price'   => (float) ($a->unit_cost_price ?? 0),
                'unit_selling_price'=> (float) ($a->unit_selling_price ?? 0),
                'total_value_change'=> (float) ($a->total_value_change ?? 0),
                'adjusted_by'       => $a->createdBy?->name ?? '-',
                'variant' => [
                    'name'         => $variant?->name ?? '-',
                    'sku'          => $variant?->sku ?? '-',
                    'image_url'    => $variant?->image_url,
                    'product_name' => $variant?->product?->name ?? '',
                ],
                'department' => $item?->departmentItem?->name ?? '-',
                'location'   => $item?->itemLocation?->name ?? '-',
            ],
        ]);
    }
        
    /**
     * ABC Analysis Report with Pure Eloquent
     */
    public function abcAnalysis(Request $request)
    {
        $tenantId = $this->getTenantId();

        $startDate    = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate      = $request->get('end_date', now()->format('Y-m-d'));
        $departmentId = $request->get('department_id');
        $locationId   = $request->get('location_id');
        $variantId    = $request->get('variant_id');
        $perPage      = (int) $request->get('per_page', 15);

        $departments = Department::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $locations   = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $variants    = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)->with(['product:id,name'])
            ->orderBy('name')->get(['id', 'name', 'sku', 'product_id']);

        return view('reports.inventory.abc-analysis', compact(
            'departments', 'locations', 'variants',
            'startDate', 'endDate', 'departmentId', 'locationId', 'variantId', 'perPage'
        ));
    }

    public function abcAnalysisData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('inventory reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('inventory_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate    = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate      = $request->get('end_date', now()->format('Y-m-d'));
        $departmentId = $request->get('department_id');
        $locationId   = $request->get('location_id');
        $variantId    = $request->get('variant_id');
        $perPage      = (int) $request->get('per_page', 15);
        $page         = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        // ─── Inventory items ────────────────────────────────────
        $query = InventoryItems::with([
                'variant.product:id,name',
                'variant:id,name,sku,barcode,image_url,product_id,grand_total_cost_price,supplier_cost_price,total_shipping_cost,ura_taxes_applied,additional_expenses',
                'departmentItem:id,name',
                'itemLocation:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->where('quantity_allocated', '>', 0)
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->when($locationId,   fn($q) => $q->where('location_id',   $locationId))
            ->when($variantId,    fn($q) => $q->where('variant_id',    $variantId));

        $allItems = $query->get();

        // ─── Movement logs (single-shop path) ──────────────────
        $logsByVariant = SingleShopInventoryLog::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->get()
            ->groupBy('variant_id');

        // ─── Metrics per item ──────────────────────────────────
        $itemsWithMetrics = $allItems->map(function ($item) use ($logsByVariant) {
            $cost = $this->variantCost($item->variant);
            $qty  = (float) ($item->quantity_allocated ?? 0);

            $variantLogs   = $logsByVariant->get($item->variant_id, collect());
            $totalMovement = $variantLogs->sum(fn($l) => abs($l->quantity_change));

            $item->inventory_value = $qty * $cost;
            $item->total_movement  = $totalMovement;
            $item->cost_price      = $cost;
            return $item;
        });

        $totalValue = $itemsWithMetrics->sum('inventory_value');

        // ─── ABC bucketing ─────────────────────────────────────
        $sortedItems = $itemsWithMetrics->sortByDesc('inventory_value')->values();
        $cumulative  = 0.0;
        $buckets = [
            'A' => ['items' => [], 'value' => 0.0, 'count' => 0, 'percentage' => 0.0],
            'B' => ['items' => [], 'value' => 0.0, 'count' => 0, 'percentage' => 0.0],
            'C' => ['items' => [], 'value' => 0.0, 'count' => 0, 'percentage' => 0.0],
        ];

        foreach ($sortedItems as $item) {
            $pct = $totalValue > 0 ? ($item->inventory_value / $totalValue) * 100 : 0;
            $cumulative += $pct;

            $cat = $cumulative <= 80 ? 'A' : ($cumulative <= 95 ? 'B' : 'C');

            $item->abc_category          = $cat;
            $item->value_percentage      = $pct;
            $item->cumulative_percentage = $cumulative;

            $buckets[$cat]['items'][]  = $item;
            $buckets[$cat]['value']   += $item->inventory_value;
            $buckets[$cat]['count']++;
        }

        foreach ($buckets as $cat => &$b) {
            $b['percentage'] = $totalValue > 0 ? ($b['value'] / $totalValue) * 100 : 0;
        }
        unset($b);

        // ─── Pagination ────────────────────────────────────────
        $total = $sortedItems->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;
        $pageItems = $sortedItems->slice($offset, $perPage)->values();

        $rows = $pageItems->map(function ($item) use ($sortedItems) {
            $variant = $item->variant;
            $idx = $sortedItems->search(fn($i) => $i->id === $item->id);

            return (object) [
                'id'                    => $item->id,
                'rank'                  => $idx !== false ? $idx + 1 : 0,
                'abc_category'          => $item->abc_category,
                'sku'                   => $variant->sku ?? '-',
                'barcode'               => $variant->barcode ?? null,
                'variant_name'          => $variant->name ?? '-',
                'product_name'          => $variant->product->name ?? '',
                'image_url'             => $variant->image_url ?? null,
                'department_name'       => $item->departmentItem->name ?? '-',
                'inventory_value'       => round($item->inventory_value, 2),
                'value_percentage'      => round($item->value_percentage, 2),
                'cumulative_percentage' => round($item->cumulative_percentage, 2),
            ];
        });

        // ─── Chart payloads ────────────────────────────────────
        $valueChart = [
            ['name' => __('pagination.abc_category_a'), 'value' => round($buckets['A']['value'], 2), 'color' => 'danger', 'count' => $buckets['A']['count']],
            ['name' => __('pagination.abc_category_b'), 'value' => round($buckets['B']['value'], 2), 'color' => 'warning', 'count' => $buckets['B']['count']],
            ['name' => __('pagination.abc_category_c'), 'value' => round($buckets['C']['value'], 2), 'color' => 'success', 'count' => $buckets['C']['count']],
        ];

        $countChart = [
            ['name' => __('pagination.abc_category_a'), 'value' => $buckets['A']['count'], 'color' => 'danger'],
            ['name' => __('pagination.abc_category_b'), 'value' => $buckets['B']['count'], 'color' => 'warning'],
            ['name' => __('pagination.abc_category_c'), 'value' => $buckets['C']['count'], 'color' => 'success'],
        ];

        // ─── Pareto (top 20) ────────────────────────────────────
        $paretoItems = $sortedItems->take(20);
        $paretoSum   = 0.0;
        $paretoRows  = [];
        foreach ($paretoItems as $item) {
            $paretoSum += $item->inventory_value;
            $paretoRows[] = [
                'name'     => mb_strimwidth($item->variant->name ?? '-', 0, 20, '…'),
                'value'    => round($item->inventory_value, 2),
                'cum_pct'  => $totalValue > 0 ? round(($paretoSum / $totalValue) * 100, 2) : 0,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_value'      => round($totalValue, 2),
                    'total_items'      => $total,
                    'categories' => [
                        'A' => ['value' => round($buckets['A']['value'], 2), 'count' => $buckets['A']['count'], 'percentage' => round($buckets['A']['percentage'], 2)],
                        'B' => ['value' => round($buckets['B']['value'], 2), 'count' => $buckets['B']['count'], 'percentage' => round($buckets['B']['percentage'], 2)],
                        'C' => ['value' => round($buckets['C']['value'], 2), 'count' => $buckets['C']['count'], 'percentage' => round($buckets['C']['percentage'], 2)],
                    ],
                ],
                'items'         => $rows,
                'value_chart'   => $valueChart,
                'count_chart'   => $countChart,
                'pareto'        => $paretoRows,
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
     * Movement Analysis Report using Multi-Shop Inventory Data
     */
    public function movementAnalysis(Request $request)
    {
        $tenantId = $this->getTenantId();

        $startDate    = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate      = $request->get('end_date', now()->format('Y-m-d'));
        $movementType = $request->get('movement_type', 'all');
        $departmentId = $request->get('department_id');
        $locationId   = $request->get('location_id');
        $variantId    = $request->get('variant_id');
        $perPage      = (int) $request->get('per_page', 15);

        $departments = Department::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $locations   = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $variants    = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)->with(['product:id,name'])
            ->orderBy('name')->get(['id', 'name', 'sku', 'product_id']);

        return view('reports.inventory.movement-analysis', compact(
            'departments', 'locations', 'variants',
            'startDate', 'endDate', 'movementType', 'departmentId', 'locationId', 'variantId', 'perPage'
        ));
    }

    public function movementAnalysisData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('inventory reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('inventory_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate    = $request->get('start_date', now()->subDays(90)->format('Y-m-d'));
        $endDate      = $request->get('end_date', now()->format('Y-m-d'));
        $movementType = $request->get('movement_type', 'all');
        $departmentId = $request->get('department_id');
        $locationId   = $request->get('location_id');
        $variantId    = $request->get('variant_id');
        $perPage      = (int) $request->get('per_page', 15);
        $page         = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $daysInPeriod = max(1, Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1);

        // ─── Sales transactions ─────────────────────────────────
        $query = InventoryTransactions::with([
                'inventoryItems.variant:id,name,sku,barcode,image_url,product_id',
                'inventoryItems.variant.product:id,name',
                'inventoryItems.departmentItem:id,name',
                'inventoryItems.itemLocation:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('type', 'sale');

        if ($departmentId) $query->whereHas('inventoryItems', fn($q) => $q->where('department_id', $departmentId));
        if ($locationId)   $query->whereHas('inventoryItems', fn($q) => $q->where('location_id', $locationId));
        if ($variantId)    $query->whereHas('inventoryItems', fn($q) => $q->where('variant_id', $variantId));

        $allTransactions = $query->get();

        $transactionsByVariant = $allTransactions->groupBy(
            fn($t) => $t->inventoryItems->variant_id ?? null
        )->filter();

        // ─── Build movement data ────────────────────────────────
        $movementCollection = collect();

        foreach ($transactionsByVariant as $vid => $variantTx) {
            $first = $variantTx->first();
            $item  = $first->inventoryItems;
            $variant = $item?->variant;
            if (!$variant) continue;

            $totalMovement    = $variantTx->sum(fn($t) => abs((float) $t->quantity));
            $transactionCount = $variantTx->count();
            $firstMovement    = $variantTx->min('created_at');
            $lastMovement     = $variantTx->max('created_at');
            $avgDaily         = $totalMovement / $daysInPeriod;

            $daysSinceLast = $lastMovement ? Carbon::parse($lastMovement)->diffInDays(now()) : 999;

            if ($avgDaily >= 10) {
                $movementCategory = 'fast';
                $movementLabel    = __('pagination.fast_moving');
                $movementColor    = 'success';
            } elseif ($avgDaily >= 1) {
                $movementCategory = 'slow';
                $movementLabel    = __('pagination.slow_moving');
                $movementColor    = 'warning';
            } else {
                $movementCategory = 'non-moving';
                $movementLabel    = __('pagination.non_moving');
                $movementColor    = 'danger';
            }

            $movementCollection->push((object) [
                'variant_id'              => $vid,
                'sku'                     => $variant->sku ?? '-',
                'barcode'                 => $variant->barcode ?? null,
                'variant_name'            => $variant->name,
                'product_name'            => $variant->product->name ?? '',
                'image_url'               => $variant->image_url ?? null,
                'total_movement'          => round($totalMovement, 2),
                'transaction_count'       => $transactionCount,
                'first_movement'          => $firstMovement?->format('Y-m-d'),
                'last_movement'           => $lastMovement?->format('Y-m-d'),
                'avg_daily_movement'      => round($avgDaily, 2),
                'days_since_last_movement'=> $daysSinceLast,
                'current_stock'           => (float) ($item->quantity_allocated ?? 0),
                'movement_category'       => $movementCategory,
                'movement_label'          => $movementLabel,
                'movement_color'          => $movementColor,
            ]);
        }

        $sortedMovement = $movementCollection->sortByDesc('total_movement')->values();

        // ─── Stats (from full set) ──────────────────────────────
        $movementStats = [
            'fast_moving'        => $sortedMovement->where('movement_category', 'fast')->count(),
            'slow_moving'        => $sortedMovement->where('movement_category', 'slow')->count(),
            'non_moving'         => $sortedMovement->where('movement_category', 'non-moving')->count(),
            'total_items'        => $sortedMovement->count(),
            'total_movement'     => round($sortedMovement->sum('total_movement'), 2),
            'avg_daily_movement' => round($sortedMovement->avg('avg_daily_movement') ?? 0, 2),
        ];

        // ─── Filter by movement type ───────────────────────────
        $filtered = $movementType !== 'all'
            ? $sortedMovement->filter(fn($i) => $i->movement_category === $movementType)->values()
            : $sortedMovement;

        // ─── Pagination ────────────────────────────────────────
        $total    = $filtered->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;
        $paginated = $filtered->slice($offset, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'stats'      => $movementStats,
                'items'      => $paginated,
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
     * Inventory Valuation Report
     */
    public function valuation(Request $request)
    {
        $tenantId = $this->getTenantId();

        $departmentId    = $request->get('department_id');
        $locationId      = $request->get('location_id');
        $variantId       = $request->get('variant_id');
        $valuationMethod = $request->get('valuation_method', 'cost');
        $perPage         = (int) $request->get('per_page', 15);

        $departments = Department::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $locations   = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $variants    = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)->with(['product:id,name'])
            ->orderBy('name')->get(['id', 'name', 'sku', 'product_id']);

        return view('reports.inventory.valuation', compact(
            'departments', 'locations', 'variants',
            'departmentId', 'locationId', 'variantId', 'valuationMethod', 'perPage'
        ));
    }

    public function valuationData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('inventory reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('inventory_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $departmentId    = $request->get('department_id');
        $locationId      = $request->get('location_id');
        $variantId       = $request->get('variant_id');
        $valuationMethod = $request->get('valuation_method', 'cost');
        $perPage         = (int) $request->get('per_page', 15);
        $page            = max(1, (int) $request->get('page', 1));

        // ─── Inventory items ────────────────────────────────────
        $query = InventoryItems::with([
                'variant.product:id,name',
                'variant:id,name,sku,barcode,image_url,product_id,grand_total_cost_price,supplier_cost_price,total_shipping_cost,ura_taxes_applied,additional_expenses,selling_price',
                'departmentItem:id,name',
                'itemLocation:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->where('quantity_allocated', '>', 0);

        if ($departmentId) $query->where('department_id', $departmentId);
        if ($locationId)   $query->where('location_id',   $locationId);
        if ($variantId)    $query->where('variant_id',    $variantId);

        $allItems = $query->get();

        // ─── Per-scope pricing override map ────────────────────
        $inventoryPricing = InventoryItems::where('tenant_id', $tenantId)
            ->whereIn('variant_id', $allItems->pluck('variant_id')->unique()->filter()->toArray())
            ->where('has_custom_pricing', 1)
            ->get(['variant_id', 'location_id', 'department_id', 'selling_price'])
            ->keyBy(fn($r) => "{$r->variant_id}|{$r->location_id}|{$r->department_id}");

        $itemsWithValuation = $allItems->map(function ($item) use ($inventoryPricing) {
            $costPrice    = $this->variantCost($item->variant);
            $sellingPrice = $this->resolveScopeSellingPrice(
                $item->variant,
                $item->location_id,
                $item->department_id,
                $inventoryPricing
            );
            $qty = (float) ($item->quantity_allocated ?? 0);

            $valuation      = $qty * $costPrice;
            $potentialProfit= $qty * max(0, $sellingPrice - $costPrice);
            $margin         = $costPrice > 0 ? (($sellingPrice - $costPrice) / $costPrice) * 100 : 0;

            $item->valuation_value  = $valuation;
            $item->potential_profit = $potentialProfit;
            $item->profit_margin    = $margin;
            $item->cost_price       = $costPrice;
            $item->selling_price    = $sellingPrice;

            return $item;
        });

        // ─── Summary ───────────────────────────────────────────
        $totalValue    = $itemsWithValuation->sum('valuation_value');
        $totalQuantity = $itemsWithValuation->sum('quantity_allocated');
        $totalProfit   = $itemsWithValuation->sum('potential_profit');

        $summary = [
            'total_items'       => $itemsWithValuation->count(),
            'total_quantity'    => round($totalQuantity, 2),
            'total_value'       => round($totalValue, 2),
            'avg_unit_cost'     => $totalQuantity > 0 ? round($totalValue / $totalQuantity, 2) : 0,
            'avg_unit_price'    => round($itemsWithValuation->avg(fn($i) => $i->selling_price ?? 0), 2),
            'potential_profit'  => round($totalProfit, 2),
            'avg_profit_margin' => round($itemsWithValuation->avg('profit_margin') ?? 0, 2),
        ];

        // ─── Aggregations ──────────────────────────────────────
        $byDept = $itemsWithValuation->groupBy('department_id')->map(function ($items) {
            $d = $items->first()->departmentItem;
            return [
                'name'  => $d?->name ?? 'Unspecified',
                'value' => round($items->sum('valuation_value'), 2),
                'count' => $items->count(),
            ];
        })->sortByDesc('value')->values();

        $byLoc = $itemsWithValuation->groupBy('location_id')->map(function ($items) {
            $l = $items->first()->itemLocation;
            return [
                'name'  => $l?->name ?? 'Unspecified',
                'value' => round($items->sum('valuation_value'), 2),
                'count' => $items->count(),
            ];
        })->sortByDesc('value')->values();

        // ─── Pagination ────────────────────────────────────────
        $sorted    = $itemsWithValuation->sortByDesc('valuation_value')->values();
        $total     = $sorted->count();
        $lastPage  = max(1, (int) ceil($total / $perPage));
        $page      = min($page, $lastPage);
        $offset    = ($page - 1) * $perPage;
        $pageItems = $sorted->slice($offset, $perPage)->values();

        $rows = $pageItems->map(function ($item) {
            $variant = $item->variant;
            return (object) [
                'id'                 => $item->id,
                'sku'                => $variant->sku ?? '-',
                'barcode'            => $variant->barcode ?? null,
                'variant_name'       => $variant->name ?? '-',
                'product_name'       => $variant->product->name ?? '',
                'image_url'          => $variant->image_url ?? null,
                'department_name'    => $item->departmentItem->name ?? '-',
                'location_name'      => $item->itemLocation->name ?? '-',
                'quantity_allocated' => (float) ($item->quantity_allocated ?? 0),
                'cost_price'         => round($item->cost_price, 2),
                'selling_price'      => round($item->selling_price, 2),
                'valuation_value'    => round($item->valuation_value, 2),
                'potential_profit'   => round($item->potential_profit, 2),
                'profit_margin'      => round($item->profit_margin, 2),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'summary'    => $summary,
                'by_dept'    => $byDept,
                'by_loc'     => $byLoc,
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


    /**
     * Dead Stock Report with Pure Eloquent - Fixed
     */
    public function deadStock(Request $request)
    {
        $tenantId = $this->getTenantId();

        $departmentId   = $request->get('department_id');
        $locationId     = $request->get('location_id');
        $variantId      = $request->get('variant_id');
        $daysThreshold  = (int) $request->get('days_threshold', 180);
        $includeExpired = $request->boolean('include_expired');
        $perPage        = (int) $request->get('per_page', 15);

        $departments = Department::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $locations   = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $variants    = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)->with(['product:id,name'])
            ->orderBy('name')->get(['id', 'name', 'sku', 'product_id']);

        return view('reports.inventory.dead-stock', compact(
            'departments', 'locations', 'variants',
            'departmentId', 'locationId', 'variantId', 'daysThreshold', 'includeExpired', 'perPage'
        ));
    }

    public function deadStockData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('inventory reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('inventory_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $departmentId   = $request->get('department_id');
        $locationId     = $request->get('location_id');
        $variantId      = $request->get('variant_id');
        $daysThreshold  = (int) $request->get('days_threshold', 180);
        $includeExpired = $request->boolean('include_expired');
        $perPage        = (int) $request->get('per_page', 15);
        $page           = max(1, (int) $request->get('page', 1));

        // ─── Items in stock ────────────────────────────────────
        $allItems = InventoryItems::with([
                'variant.product:id,name',
                'variant:id,name,sku,barcode,image_url,product_id,grand_total_cost_price,supplier_cost_price,total_shipping_cost,ura_taxes_applied,additional_expenses',
                'departmentItem:id,name',
                'itemLocation:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->where('quantity_allocated', '>', 0)
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->when($locationId,   fn($q) => $q->where('location_id',   $locationId))
            ->when($variantId,    fn($q) => $q->where('variant_id',    $variantId))
            ->get();

        // ─── Last-movement per variant (sale type) ────────────
        $salesByVariant = InventoryTransactions::where('tenant_id', $tenantId)
            ->where('type', 'sale')
            ->where('quantity', '<', 0)
            ->with(['inventoryItems:id,variant_id'])
            ->get()
            ->groupBy(fn($t) => $t->inventoryItems->variant_id ?? null);

        // ─── Build dead stock collection ───────────────────────
        $deadCollection = collect();

        foreach ($allItems as $item) {
            $variant = $item->variant;
            if (!$variant) continue;

            $tx = $salesByVariant->get($item->variant_id, collect());

            $lastMovementDate = $tx->isNotEmpty() ? $tx->max('created_at') : $item->created_at;
            $daysIdle = $lastMovementDate ? Carbon::parse($lastMovementDate)->diffInDays(now()) : 999;

            $totalMovement = $tx->sum(fn($t) => abs((float) $t->quantity));

            $isExpired = $item->expiry_date && Carbon::parse($item->expiry_date)->lt(now());

            $cost = $this->variantCost($variant);
            $invValue = (float) ($item->quantity_allocated ?? 0) * $cost;

            $isDead = $daysIdle >= $daysThreshold || ($includeExpired && $isExpired);
            if (!$isDead) continue;

            $deadCollection->push((object) [
                'id'                 => $item->id,
                'sku'                => $variant->sku ?? '-',
                'barcode'            => $variant->barcode ?? null,
                'variant_name'       => $variant->name ?? '-',
                'product_name'       => $variant->product->name ?? '',
                'image_url'          => $variant->image_url ?? null,
                'department_name'    => $item->departmentItem->name ?? '-',
                'location_name'      => $item->itemLocation->name ?? '-',
                'quantity_on_hand'   => (float) ($item->quantity_allocated ?? 0),
                'last_movement_date' => $lastMovementDate?->format('Y-m-d'),
                'days_idle'          => $daysIdle,
                'is_expired'         => $isExpired,
                'expiry_date'        => $item->expiry_date ? Carbon::parse($item->expiry_date)->format('Y-m-d') : null,
                'inventory_value'    => round($invValue, 2),
                'cost_price'         => round($cost, 2),
                'total_movement'     => round($totalMovement, 2),
            ]);
        }

        $sorted = $deadCollection->sortByDesc('days_idle')->values();

        // ─── Summary ───────────────────────────────────────────
        $summary = [
            'total_items'    => $sorted->count(),
            'total_quantity' => round($sorted->sum('quantity_on_hand'), 2),
            'total_value'    => round($sorted->sum('inventory_value'), 2),
            'avg_days_idle'  => round($sorted->avg('days_idle') ?? 0, 2),
            'expired_items'  => $sorted->where('is_expired', true)->count(),
        ];

        // ─── Idle category buckets (by qty) ───────────────────
        $idleBuckets = [
            '180_365'  => $sorted->filter(fn($i) => $i->days_idle >= 180 && $i->days_idle < 365)->sum('quantity_on_hand'),
            '365_730'  => $sorted->filter(fn($i) => $i->days_idle >= 365 && $i->days_idle < 730)->sum('quantity_on_hand'),
            'over_730' => $sorted->filter(fn($i) => $i->days_idle >= 730)->sum('quantity_on_hand'),
        ];

        // ─── Department breakdown ─────────────────────────────
        $deptBreakdown = $sorted->groupBy('department_name')->map(function ($items, $name) {
            return [
                'name'     => $name ?: 'Unspecified',
                'quantity' => round($items->sum('quantity_on_hand'), 2),
                'value'    => round($items->sum('inventory_value'), 2),
            ];
        })->sortByDesc('quantity')->values();

        // ─── Pagination ────────────────────────────────────────
        $total    = $sorted->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;
        $pageItems = $sorted->slice($offset, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'summary'    => $summary,
                'idle'       => $idleBuckets,
                'by_dept'    => $deptBreakdown,
                'items'      => $pageItems,
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
     * Excess Stock Report with Pure Eloquent
     */
    public function excessStock(Request $request)
    {
        $tenantId = $this->getTenantId();

        $departmentId    = $request->get('department_id');
        $locationId      = $request->get('location_id');
        $variantId       = $request->get('variant_id');
        $excessThreshold = (float) $request->get('excess_threshold', 1.5);
        $perPage         = (int) $request->get('per_page', 15);

        $departments = Department::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $locations   = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $variants    = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)->with(['product:id,name'])
            ->orderBy('name')->get(['id', 'name', 'sku', 'product_id']);

        return view('reports.inventory.excess-stock', compact(
            'departments', 'locations', 'variants',
            'departmentId', 'locationId', 'variantId', 'excessThreshold', 'perPage'
        ));
    }

    public function excessStockData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('inventory reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('inventory_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $departmentId    = $request->get('department_id');
        $locationId      = $request->get('location_id');
        $variantId       = $request->get('variant_id');
        $excessThreshold = (float) $request->get('excess_threshold', 1.5);
        $perPage         = (int) $request->get('per_page', 15);
        $page            = max(1, (int) $request->get('page', 1));

        // ─── Inventory items with preferred stock ──────────────
        $allItems = InventoryItems::with([
                'variant.product:id,name',
                'variant:id,name,sku,barcode,image_url,product_id,grand_total_cost_price,supplier_cost_price,total_shipping_cost,ura_taxes_applied,additional_expenses',
                'departmentItem:id,name',
                'itemLocation:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->where('quantity_allocated', '>', 0)
            ->where('preferred_stock_level', '>', 0)
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->when($locationId,   fn($q) => $q->where('location_id',   $locationId))
            ->when($variantId,    fn($q) => $q->where('variant_id',    $variantId))
            ->get();

        // ─── Build excess rows ─────────────────────────────────
        $excessCollection = collect();

        foreach ($allItems as $item) {
            $variant = $item->variant;
            if (!$variant) continue;

            $current   = (float) ($item->quantity_allocated ?? 0);
            $preferred = (float) ($item->preferred_stock_level ?? 0);
            $threshold = $preferred * $excessThreshold;
            $excessQty = max(0, $current - $threshold);

            if ($excessQty <= 0) continue;

            $excessPct = $preferred > 0 ? (($current / $preferred) - 1) * 100 : 0;
            $cost      = $this->variantCost($variant);
            $excessVal = $excessQty * $cost;

            if ($excessPct >= 200) {
                [$sev, $color, $text] = ['critical', 'danger', __('pagination.critical')];
            } elseif ($excessPct >= 100) {
                [$sev, $color, $text] = ['high', 'danger', __('pagination.high')];
            } elseif ($excessPct >= 50) {
                [$sev, $color, $text] = ['moderate', 'warning', __('pagination.moderate')];
            } else {
                [$sev, $color, $text] = ['low', 'info', __('pagination.low')];
            }

            $excessCollection->push((object) [
                'id'                  => $item->id,
                'sku'                 => $variant->sku ?? '-',
                'barcode'             => $variant->barcode ?? null,
                'variant_name'        => $variant->name ?? '-',
                'product_name'        => $variant->product->name ?? '',
                'image_url'           => $variant->image_url ?? null,
                'department_name'     => $item->departmentItem->name ?? '-',
                'location_name'       => $item->itemLocation->name ?? '-',
                'quantity_on_hand'    => round($current, 2),
                'preferred_stock'     => round($preferred, 2),
                'threshold_stock'     => round($threshold, 2),
                'excess_quantity'     => round($excessQty, 2),
                'excess_percentage'   => round($excessPct, 2),
                'excess_value'        => round($excessVal, 2),
                'cost_price'          => round($cost, 2),
                'severity'            => $sev,
                'severity_color'      => $color,
                'severity_text'       => $text,
            ]);
        }

        $sorted = $excessCollection->sortByDesc('excess_quantity')->values();

        // ─── Summary ───────────────────────────────────────────
        $summary = [
            'total_items'           => $sorted->count(),
            'total_excess_quantity' => round($sorted->sum('excess_quantity'), 2),
            'total_excess_value'    => round($sorted->sum('excess_value'), 2),
            'avg_excess_percentage' => round($sorted->avg('excess_percentage') ?? 0, 2),
            'threshold'             => $excessThreshold,
        ];

        // ─── Category buckets ─────────────────────────────────
        $categories = [
            '50_100'   => round($sorted->filter(fn($i) => $i->excess_percentage >= 50  && $i->excess_percentage < 100)->sum('excess_quantity'), 2),
            '100_200'  => round($sorted->filter(fn($i) => $i->excess_percentage >= 100 && $i->excess_percentage < 200)->sum('excess_quantity'), 2),
            'over_200' => round($sorted->filter(fn($i) => $i->excess_percentage >= 200)->sum('excess_quantity'), 2),
        ];

        // ─── Pagination ───────────────────────────────────────
        $total    = $sorted->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);
        $offset   = ($page - 1) * $perPage;
        $pageItems = $sorted->slice($offset, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'summary'    => $summary,
                'categories' => $categories,
                'items'      => $pageItems,
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
    
}