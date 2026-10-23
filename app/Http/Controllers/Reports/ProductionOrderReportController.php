<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderInput;
use App\Models\ProductionOrderOutput;
use App\Models\ProductVariant;
use App\Models\ProductCategory;
use App\Models\Location;
use App\Models\Department;
use App\Models\SingleShopInventoryLog;
use App\Models\BatchLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\PurchaseReceiptItem;

class ProductionOrderReportController extends Controller
{
    /**
     * Get current tenant ID and check permissions
     */
    private function getTenantId()
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('production reports')) {
            abort(403, __('payments.not_authorized'));
        }
        if (!tenant_can('production_reports')) {
            abort(403, __('payments.feature_not_available_in_plan'));
        }
        return $user->tenant_id;
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
     * Main Production Order Report
     */
    public function index(Request $request)
    {
        $tenantId = $this->getTenantId();
        $isSingleShop = tenant_is_single_shop($tenantId);
        
        // ─── Filter Parameters ──────────────────────────────────────────
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $status = $request->get('status', 'all');
        $locationId = $request->get('location_id');
        $variantId = $request->get('variant_id');
        $categoryId = $request->get('category_id');
        $search = $request->get('search');
        $hasPayment = $request->get('has_payment', 'all');
        $minCost = $request->get('min_cost');
        $maxCost = $request->get('max_cost');
        $perPage = (int)$request->get('per_page', 15);
        
        // ─── Build Query ─────────────────────────────────────────────────
        $query = ProductionOrder::with([
            'inputs.productVariant.product.category',
            'outputs.productVariant.product.category',
            'location',
            'createdBy',
            'startedBy',
            'completedBy',
            'paymentMethod'
        ])
        ->where('tenant_id', $tenantId)->latest();
        
        // Date range filter
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        }
        
        // Status filter
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        // Location filter
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        // Variant filter (through inputs or outputs)
        if ($variantId) {
            $query->whereHas('inputs', function($q) use ($variantId) {
                $q->where('product_variant_id', $variantId);
            })->orWhereHas('outputs', function($q) use ($variantId) {
                $q->where('product_variant_id', $variantId);
            });
        }
        
        // Category filter (through inputs or outputs)
        if ($categoryId) {
            $query->whereHas('inputs.productVariant.product', function($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            })->orWhereHas('outputs.productVariant.product', function($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }
        
        // Payment filter
        if ($hasPayment === 'with_payment') {
            $query->whereNotNull('payment_method_id');
        } elseif ($hasPayment === 'without_payment') {
            $query->whereNull('payment_method_id');
        }
        
        // Cost range filter
        if ($minCost !== null) {
            $query->where('total_cost', '>=', $minCost);
        }
        if ($maxCost !== null) {
            $query->where('total_cost', '<=', $maxCost);
        }
        
        // Search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('production_number', 'LIKE', "%{$search}%")
                  ->orWhere('notes', 'LIKE', "%{$search}%")
                  ->orWhereHas('createdBy', function($c) use ($search) {
                      $c->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('inputs.productVariant', function($v) use ($search) {
                      $v->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('sku', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('outputs.productVariant', function($v) use ($search) {
                      $v->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('sku', 'LIKE', "%{$search}%");
                  });
            });
        }
        
        // ─── Get All Orders for Summary ─────────────────────────────────
        $allOrders = $query->get();
        
        // ─── Calculate Summary Statistics ───────────────────────────────
        $summary = [
            'total_orders' => $allOrders->count(),
            'draft_count' => $allOrders->where('status', ProductionOrder::STATUS_DRAFT)->count(),
            'in_progress_count' => $allOrders->where('status', ProductionOrder::STATUS_IN_PROGRESS)->count(),
            'completed_count' => $allOrders->where('status', ProductionOrder::STATUS_COMPLETED)->count(),
            'cancelled_count' => $allOrders->where('status', ProductionOrder::STATUS_CANCELLED)->count(),
            'total_input_cost' => $allOrders->sum('total_input_cost'),
            'total_output_cost' => $allOrders->sum('total_output_cost'),
            'total_cost' => $allOrders->sum('total_cost'),
            'total_input_quantity' => $allOrders->sum('total_input_quantity'),
            'total_output_quantity' => $allOrders->sum('total_output_quantity'),
            'with_payment' => $allOrders->whereNotNull('payment_method_id')->count(),
            'without_payment' => $allOrders->whereNull('payment_method_id')->count(),
            'avg_cost' => $allOrders->count() > 0 ? $allOrders->avg('total_cost') : 0,
            'total_profit' => $allOrders->sum(function($order) {
                return $order->total_output_cost - $order->total_input_cost;
            }),
        ];
        
        // ─── Apply Pagination ────────────────────────────────────────────
        $paginatedOrders = $this->paginateCollection($allOrders, $perPage, 'page');
        
        // ─── Get Daily Trend Data ───────────────────────────────────────
        $dailyTrend = $allOrders->groupBy(function($order) {
            return $order->created_at->format('Y-m-d');
        })->map(function($items, $date) {
            return (object)[
                'date' => Carbon::parse($date)->format('M d'),
                'count' => $items->count(),
                'total_cost' => $items->sum('total_cost'),
            ];
        })->sortKeys()->values();
        
        // ─── Get Status Breakdown ───────────────────────────────────────
        $statusBreakdown = collect([
            (object)['status' => 'draft', 'label' => __('pagination.draft'), 'count' => $summary['draft_count'], 'color' => 'secondary'],
            (object)['status' => 'in_progress', 'label' => __('pagination.in_progress'), 'count' => $summary['in_progress_count'], 'color' => 'warning'],
            (object)['status' => 'completed', 'label' => __('pagination.completed'), 'count' => $summary['completed_count'], 'color' => 'success'],
            (object)['status' => 'cancelled', 'label' => __('pagination.cancelled'), 'count' => $summary['cancelled_count'], 'color' => 'danger'],
        ])->filter(fn($item) => $item->count > 0)->values();
        
        // ─── Get Filter Options ─────────────────────────────────────────
        $locations = Location::where('tenant_id', $tenantId)->get();
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('product')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);
        $categories = ProductCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        $statuses = [
            ['value' => 'all', 'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_DRAFT, 'label' => __('pagination.draft')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
            ['value' => ProductionOrder::STATUS_COMPLETED, 'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_CANCELLED, 'label' => __('pagination.cancelled')],
        ];
        
        return view('reports.production.index', compact(
            'paginatedOrders',
            'allOrders',
            'summary',
            'dailyTrend',
            'statusBreakdown',
            'locations',
            'variants',
            'categories',
            'statuses',
            'startDate',
            'endDate',
            'status',
            'locationId',
            'variantId',
            'categoryId',
            'search',
            'hasPayment',
            'minCost',
            'maxCost',
            'perPage',
            'isSingleShop'
        ));
    }

    /**
     * Get detailed view for a specific production order
     */
    public function detail($orderId)
    {
        $tenantId = $this->getTenantId();
        $isSingleShop = tenant_is_single_shop($tenantId);
        $purchaseOrderItems = $this->resolveProductionPOItems($orderId);
        
        $order = ProductionOrder::with([
            'inputs.productVariant.product.category',
            'outputs.productVariant.product.category',
            'location',
            'createdBy',
            'startedBy',
            'completedBy',
            'cancelledBy',
            'paymentMethod'
        ])
        ->where('tenant_id', $tenantId)
        ->where('id', $orderId)
        ->firstOrFail();
        
        // ─── Get Inventory Logs for this order ──────────────────────────
        $inventoryLogs = SingleShopInventoryLog::with('variant')
            ->where('order_id', $orderId)
            ->where(function ($q) {
                $q->where('source', 'production')
                ->orWhereIn('reason', [
                    'production_consumption',
                    'production_output',
                    'production_output_update',
                ]);
            })
            ->orderBy('created_at', 'desc')
            ->get();
        
        // ─── Get Batch Logs for this order ─────────────────────────────
        $batchLogs = BatchLog::where('production_order_id', $orderId)
            ->orderBy('event_date', 'desc')
            ->get();
        
        // ─── Determine if this order mixes weight-units (bagged) ───────
        $hasWeightedOutputs = $order->outputs->contains(function ($o) {
            return $o->productVariant && (float) $o->productVariant->weight > 0;
        });

        // ─── Input KG ────
        $totalInputKg = $order->inputs->sum(function ($i) {
            return $i->productVariant
                ? $i->productVariant->toKilograms($i->actual_quantity)
                : $i->actual_quantity;
        });

        // ─── Output KG ───
        $totalOutputKg = $order->outputs->sum(function ($o) {
            return $o->productVariant
                ? $o->productVariant->toKilograms($o->actual_quantity)
                : $o->actual_quantity;
        });

        // ─── Financials ─────────────────────────────────────────────────
        
        // 1. Raw Materials (COGS) — what was actually paid to the supplier
        $poCost = collect($purchaseOrderItems)->sum('subtotal');
        if ($poCost <= 0) {
            $poCost = (float) $order->total_input_cost;
        }

        // 2. Overhead — what was withdrawn to run production (labor, power, packaging, etc.)
        $paymentTransaction = \App\Models\PaymentTransactionLog::where('tenant_id', $tenantId)
            ->where('reference_table', 'production_orders')
            ->where('reference_id', $order->id)
            ->where('transaction_type', 'WITHDRAWAL')
            ->where('status', 'COMPLETED')
            ->first();
            
        $overheadCost = $paymentTransaction ? (float) $paymentTransaction->amount : 0;

        // 3. Total Cash Out = Raw Materials + Overhead
        $totalProductionCost = $poCost + $overheadCost;

        // 4. Output Selling Value (Revenue)
        $totalRevenue = $order->outputs->sum(function ($o) {
            return $o->productVariant
                ? $o->productVariant->toRevenue($o->actual_quantity)
                : 0;
        });

        // ─── ★ Cost each output by its variant's grand_total_cost ───────
        // This is only used for the per-row "Potential Profit" column in the
        // outputs table. It is NOT used to compute overall profit.
        $totalInventoryValue = 0;

        $order->outputs->transform(function($output) use (&$totalInventoryValue) {
            $qty = (float) $output->actual_quantity;
            $unitCost = (float) ($output->productVariant->grand_total_cost_price ?? 0);
            $allocatedCost = $qty * $unitCost;
            
            $totalInventoryValue += $allocatedCost;

            $output->production_cost = round($allocatedCost, 2);
            $output->unit_cost = $unitCost;

            return $output;
        });

        // ─── Loss = input − sum of all outputs (in kg) ────────────────
        $lossKg  = max(0, $totalInputKg - $totalOutputKg);
        $lossPct = $totalInputKg > 0 ? ($lossKg / $totalInputKg) * 100 : 0;

        // ─── Per-category breakdown ────────────────────────────────────
        $outputBreakdown = $order->outputs->map(function ($o) use ($totalInputKg) {
            $kg  = $o->productVariant
                ? $o->productVariant->toKilograms($o->actual_quantity)
                : $o->actual_quantity;
            $rev = $o->productVariant
                ? $o->productVariant->toRevenue($o->actual_quantity)
                : 0;

            return [
                'variant_name' => $o->productVariant->name ?? 'N/A',
                'sku'          => $o->productVariant->sku  ?? null,
                'bags'         => (float) $o->actual_quantity,
                'weight'       => (float) ($o->productVariant->weight ?? 0),
                'total_kg'     => round($kg, 2),
                'share_pct'    => $totalInputKg > 0 ? round(($kg / $totalInputKg) * 100, 2) : 0,
                'revenue'      => round($rev, 2),
            ];
        })->values();

        // ─── Profit calculations ────────────────────────────────────────
        // Gross Profit = Revenue − Raw Materials
        $grossProfit = $totalRevenue - $poCost;
        
        // Net Production Profit = Gross Profit − Overhead
        $netProfit = $grossProfit - $overheadCost;
        
        $grossMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;
        $netMargin   = $totalRevenue > 0 ? ($netProfit   / $totalRevenue) * 100 : 0;

        // ─── Metrics ────────────────────────────────────────────────────
        $metrics = [
            'total_inputs'          => $order->inputs->count(),
            'total_outputs'         => $order->outputs->count(),

            'total_input_kg'        => round($totalInputKg, 2),
            'total_output_kg'       => round($totalOutputKg, 2),
            'loss_kg'               => round($lossKg, 2),
            'loss_pct'              => round($lossPct, 2),
            'input_yield'           => $totalInputKg > 0
                ? round(($totalOutputKg / $totalInputKg) * 100, 2)
                : 0,
            'yield_mode'            => $hasWeightedOutputs ? 'weight_based' : 'quantity_based',

            // ─── Financials ─────────────────────────────────────────
            'po_cost'               => round($poCost, 2),
            'overhead_cost'         => round($overheadCost, 2),
            'total_production_cost' => round($totalProductionCost, 2),
            'inventory_value'       => round($totalInventoryValue, 2), // informational only
            
            'total_revenue'         => round($totalRevenue, 2),
            
            // ─── Profits ────────────────────────────────────────────
            'gross_profit'          => round($grossProfit, 2),
            'gross_margin'          => round($grossMargin, 2),
            'net_profit'            => round($netProfit, 2),
            'net_margin'            => round($netMargin, 2),

            // Legacy aliases
            'profit'                => round($netProfit, 2),
            'profit_margin'         => round($netMargin, 2),
            'batch_cost'            => round($poCost, 2),

            'cost_efficiency'       => $totalProductionCost > 0
                ? round(($totalRevenue / $totalProductionCost) * 100, 2)
                : 0,

            'duration_hours'        => $order->started_at && $order->completed_at
                ? $order->started_at->diffInHours($order->completed_at)
                : 0,

            'output_breakdown'      => $outputBreakdown,
        ];
        
        // ─── Get Inputs with Quality Stats ─────────────────────────────
        $inputStats = [
            'total_planned' => $order->inputs->sum('planned_quantity'),
            'total_actual'  => $order->inputs->sum('actual_quantity'),
            'total_waste'   => $order->inputs->sum('waste_quantity'),
            'accepted'      => $order->inputs->where('quality_status', ProductionOrderInput::QUALITY_ACCEPTED)->count(),
            'rejected'      => $order->inputs->where('quality_status', ProductionOrderInput::QUALITY_REJECTED)->count(),
            'pending'       => $order->inputs->where('quality_status', ProductionOrderInput::QUALITY_PENDING)->count(),
        ];
        
        // ─── Get Outputs with Quality Stats ────────────────────────────
        $outputStats = [
            'total_planned'   => $order->outputs->sum('planned_quantity'),
            'total_actual'    => $order->outputs->sum('actual_quantity'),
            'total_defective' => $order->outputs->sum('defective_quantity'),
            'approved'        => $order->outputs->where('quality_status', ProductionOrderOutput::QUALITY_APPROVED)->count(),
            'rejected'        => $order->outputs->where('quality_status', ProductionOrderOutput::QUALITY_REJECTED)->count(),
            'pending'         => $order->outputs->where('quality_status', ProductionOrderOutput::QUALITY_PENDING)->count(),
            'yield_rate'      => $order->outputs->sum('planned_quantity') > 0 
                ? ($order->outputs->sum('actual_quantity') / $order->outputs->sum('planned_quantity')) * 100 
                : 0,
        ];
        
        return response()->json([
            'order'                => $order,
            'metrics'              => $metrics,
            'input_stats'          => $inputStats,
            'output_stats'         => $outputStats,
            'inventory_logs'       => $inventoryLogs,
            'batch_logs'           => $batchLogs,
            'purchase_order_items' => $purchaseOrderItems,
            'is_single_shop'       => $isSingleShop,
        ]);
    }

    public function data(Request $request)
    {
        $tenantId = $this->getTenantId();

        $startDate   = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate     = $request->get('end_date', now()->format('Y-m-d'));
        $status      = $request->get('status', 'all');
        $locationId  = $request->get('location_id');
        $variantId   = $request->get('variant_id');
        $categoryId  = $request->get('category_id');
        $search      = trim((string) $request->get('search', ''));
        $hasPayment  = $request->get('has_payment', 'all');
        $minCost     = $request->get('min_cost');
        $maxCost     = $request->get('max_cost');
        $perPage     = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100], true)) $perPage = 15;

        $query = ProductionOrder::with([
                'location:id,name',
                'createdBy:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->latest();

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        }

        if ($status && $status !== 'all')       $query->where('status', $status);
        if ($locationId)                        $query->where('location_id', $locationId);
        if ($categoryId) {
            $query->where(function ($q) use ($categoryId) {
                $q->whereHas('inputs.productVariant.product',  fn($s) => $s->where('category_id', $categoryId))
                ->orWhereHas('outputs.productVariant.product', fn($s) => $s->where('category_id', $categoryId));
            });
        }
        if ($variantId) {
            $query->where(function ($q) use ($variantId) {
                $q->whereHas('inputs',  fn($s) => $s->where('product_variant_id', $variantId))
                ->orWhereHas('outputs', fn($s) => $s->where('product_variant_id', $variantId));
            });
        }
        if ($hasPayment === 'with_payment')    $query->whereNotNull('payment_method_id');
        if ($hasPayment === 'without_payment') $query->whereNull('payment_method_id');
        if ($minCost !== null && $minCost !== '') $query->where('total_cost', '>=', $minCost);
        if ($maxCost !== null && $maxCost !== '') $query->where('total_cost', '<=', $maxCost);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('production_number', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%")
                ->orWhereHas('createdBy', fn($c) => $c->where('name', 'like', "%{$search}%"))
                ->orWhereHas('inputs.productVariant', fn($v) =>
                    $v->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                ->orWhereHas('outputs.productVariant', fn($v) =>
                    $v->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
            });
        }

        // Summary from the full filtered set
        $all = (clone $query)->get();

        $summary = [
            'total_orders'           => $all->count(),
            'draft_count'            => $all->where('status', ProductionOrder::STATUS_DRAFT)->count(),
            'in_progress_count'      => $all->where('status', ProductionOrder::STATUS_IN_PROGRESS)->count(),
            'completed_count'        => $all->where('status', ProductionOrder::STATUS_COMPLETED)->count(),
            'cancelled_count'        => $all->where('status', ProductionOrder::STATUS_CANCELLED)->count(),
            'total_input_cost'       => (float) $all->sum('total_input_cost'),
            'total_output_cost'      => (float) $all->sum('total_output_cost'),
            'total_cost'             => (float) $all->sum('total_cost'),
            'total_input_quantity'   => (float) $all->sum('total_input_quantity'),
            'total_output_quantity'  => (float) $all->sum('total_output_quantity'),
            'total_profit'           => (float) $all->sum(fn($o) => $o->total_output_cost - $o->total_input_cost),
            'with_payment'           => $all->whereNotNull('payment_method_id')->count(),
            'avg_cost'               => $all->count() > 0 ? (float) $all->avg('total_cost') : 0,
        ];

        $paginator = $query->paginate($perPage);

        $rows = collect($paginator->items())->map(function (ProductionOrder $order) {
            return [
                'id'                     => $order->id,
                'production_number'      => $order->production_number,
                'created_at'             => optional($order->created_at)->format('Y-m-d H:i'),
                'status'                 => $order->status,
                'status_label'           => $order->status_label ?? ucfirst(str_replace('_', ' ', $order->status)),
                'status_badge'           => $order->status_badge ?? 'secondary',
                'location_name'          => $order->location?->name,
                'total_input_quantity'   => (float) ($order->total_input_quantity ?? 0),
                'total_output_quantity'  => (float) ($order->total_output_quantity ?? 0),
                'total_input_cost'       => (float) ($order->total_input_cost ?? 0),
                'total_output_cost'      => (float) ($order->total_output_cost ?? 0),
                'created_by_name'        => $order->createdBy?->name,
                'payment_method_id'      => $order->payment_method_id,
            ];
        });

        return response()->json([
            'success'    => true,
            'data'       => $rows,
            'summary'    => $summary,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * Walk the batch logs of this production order and resolve every
     * purchase order item that contributed raw material to it.
     *
     * Groups by PO item, sums the consumed quantity, and computes a
     * subtotal at the PO's unit cost. Returns a flat list with the
     * PO context attached for display.
     */
    private function resolveProductionPOItems(int $productionOrderId): array
    {
        // 1. Get all depletion logs for this production order that
        //    carry a purchase_order_id (i.e. came from a purchased batch).
        $depletionLogs = BatchLog::query()
            ->where('production_order_id', $productionOrderId)
            ->where('type', BatchLog::TYPE_DEPLETED)
            ->whereNotNull('purchase_order_id')
            ->get();

        if ($depletionLogs->isEmpty()) {
            return [];
        }

        // 2. Resolve the receipt item → PO item for each depletion log.
        //    The batch_id on the log points to purchase_receipt_items.
        $receiptItemIds = $depletionLogs->pluck('batch_id')->filter()->unique()->values();

        $receiptItems = PurchaseReceiptItem::query()
            ->whereIn('id', $receiptItemIds)
            ->with([
                'purchaseOrderItem.productVariant',
                'purchaseOrderItem.purchaseOrder.supplier',
            ])
            ->get()
            ->keyBy('id');

        // 3. Group the depletion logs by PO item and sum quantities.
        $grouped = [];

        foreach ($depletionLogs as $log) {
            $receiptItem = $receiptItems->get($log->batch_id);
            $poItem      = $receiptItem?->purchaseOrderItem;

            if (!$poItem) {
                continue;
            }

            $key = $poItem->id;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'po_item_id'          => $poItem->id,
                    'purchase_order_id'   => $poItem->purchase_order_id,
                    'po_number'           => $poItem->purchaseOrder?->po_number,
                    'supplier_id'         => $poItem->purchaseOrder?->supplier_id,
                    'supplier_name'       => $poItem->purchaseOrder?->supplier?->name,
                    'product_name'        => $poItem->product_name,
                    'sku'                 => $poItem->sku,
                    'unit_cost'           => (float) $poItem->unit_cost,
                    'ordered_quantity'    => (int) $poItem->quantity,
                    'received_quantity'   => (int) $poItem->received_quantity,
                    'consumed_quantity'   => 0.0,
                    'batches'             => [],
                ];
            }

            $consumed = abs((float) $log->quantity_change);
            $grouped[$key]['consumed_quantity'] += $consumed;
            $grouped[$key]['batches'][] = [
                'batch_id'         => $log->batch_id,
                'batch_number'     => $log->batch_number,
                'quantity_consumed'=> $consumed,
                'unit_cost'        => (float) $log->unit_cost,
                'total_cost'       => (float) $log->total_cost,
                'event_date'       => optional($log->event_date)->toDateTimeString(),
            ];
        }

        // 4. Compute totals per PO item.
        return collect($grouped)
            ->map(function ($row) {
                $row['subtotal'] = round($row['consumed_quantity'] * $row['unit_cost'], 2);
                return $row;
            })
            ->values()
            ->all();
    }

    /**
     * Export production orders to Excel/CSV
     */
    public function export(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $status = $request->get('status', 'all');
        
        $query = ProductionOrder::with(['inputs', 'outputs', 'location', 'createdBy'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        $orders = $query->get();
        
        // ─── Build Export Data ──────────────────────────────────────────
        $exportData = $orders->map(function($order) {
            return [
                'Production Number' => $order->production_number,
                'Status' => $order->status_label,
                'Created At' => $order->created_at->format('Y-m-d H:i'),
                'Started At' => $order->started_at ? $order->started_at->format('Y-m-d H:i') : '-',
                'Completed At' => $order->completed_at ? $order->completed_at->format('Y-m-d H:i') : '-',
                'Location' => $order->location->name ?? '-',
                'Created By' => $order->createdBy->name ?? '-',
                'Input Quantity' => number_format($order->total_input_quantity, 2),
                'Output Quantity' => number_format($order->total_output_quantity, 2),
                'Input Cost' => number_format($order->total_input_cost, 2),
                'Output Cost' => number_format($order->total_output_cost, 2),
                'Total Cost' => number_format($order->total_cost, 2),
                'Notes' => $order->notes ?? '-',
            ];
        });
        
        // Return as JSON for export
        return response()->json([
            'success' => true,
            'data' => $exportData,
            'filename' => 'production_orders_' . date('Y_m_d') . '.csv',
        ]);
    }


    /**
     * Production Summary Report — PAGE SHELL.
     * Only filter options + initial filter state. No heavy queries.
     */
    public function summary(Request $request)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = tenant_is_single_shop($tenantId);

        $startDate  = $request->get('start_date', now()->subMonths(6)->format('Y-m-d'));
        $endDate    = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $status     = $request->get('status', 'all');

        $locations = Location::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $statuses = [
            ['value' => 'all',                               'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_DRAFT,       'label' => __('pagination.draft')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
            ['value' => ProductionOrder::STATUS_COMPLETED,   'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_CANCELLED,   'label' => __('pagination.cancelled')],
        ];

        return view('reports.production.summary', compact(
            'locations', 'statuses', 'startDate', 'endDate', 'locationId', 'status', 'isSingleShop'
        ));
    }

    /**
     * Production Summary Report — DATA.
     * Returns JSON. Guards return JSON 403 (not HTML abort).
     */
    public function summaryData(Request $request)
    {
        // JSON 403 if unauthorized (mirrors AccountingController)
        $user = auth()->user();
        if (!$user->hasPermissionTo('production reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate  = $request->get('start_date', now()->subMonths(6)->format('Y-m-d'));
        $endDate    = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $status     = $request->get('status', 'all');

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $query = ProductionOrder::with([
                'inputs.productVariant.product.category',
                'outputs.productVariant.product.category',
                'location:id,name',
                'paymentMethod:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        if ($locationId)                  $query->where('location_id', $locationId);
        if ($status && $status !== 'all') $query->where('status', $status);

        $orders = $query->get();

        $agg = [
            'input_kg' => 0.0, 'output_kg' => 0.0,
            'revenue' => 0.0, 'batch_cost' => 0.0, 'loss_kg' => 0.0,
            'waste_qty' => 0.0, 'defective_qty' => 0.0,
            'input_accepted' => 0, 'input_rejected' => 0, 'input_total' => 0,
            'with_payment' => 0, 'without_payment' => 0,
        ];
        $monthly = $byLoc = $byProd = $byMat = [];
        $completedCount = $inProgressCount = $draftCount = $cancelledCount = 0;
        $durSum = 0.0; $durN = 0;

        foreach ($orders as $order) {
            switch ($order->status) {
                case ProductionOrder::STATUS_COMPLETED:   $completedCount++;  break;
                case ProductionOrder::STATUS_IN_PROGRESS: $inProgressCount++; break;
                case ProductionOrder::STATUS_DRAFT:       $draftCount++;      break;
                case ProductionOrder::STATUS_CANCELLED:   $cancelledCount++;  break;
            }

            if ($order->started_at && $order->completed_at) {
                $durSum += $order->started_at->diffInHours($order->completed_at);
                $durN++;
            }

            $order->payment_method_id ? $agg['with_payment']++ : $agg['without_payment']++;

            $inKg = 0.0;
            foreach ($order->inputs as $input) {
                $v = $input->productVariant;
                if (!$v) continue;

                $w  = (float) $v->weight;
                $q  = (float) $input->actual_quantity;
                $kg = $w > 0 ? $q * $w : $q;

                $inKg              += $kg;
                $agg['input_kg']   += $kg;
                $agg['waste_qty']  += (float) $input->waste_quantity;
                $agg['input_total']++;

                if ($input->quality_status === ProductionOrderInput::QUALITY_ACCEPTED) {
                    $agg['input_accepted']++;
                } elseif ($input->quality_status === ProductionOrderInput::QUALITY_REJECTED) {
                    $agg['input_rejected']++;
                }

                $vid = $v->id;
                $byMat[$vid] ??= [
                    'variant_id' => $vid, 'variant_name' => $v->name, 'sku' => $v->sku,
                    'category' => $v->product->category->name ?? 'Uncategorized',
                    'total_quantity' => 0.0, 'total_kg' => 0.0, 'total_cost' => 0.0,
                    'order_ids' => [],
                ];
                $byMat[$vid]['total_quantity'] += $q;
                $byMat[$vid]['total_kg']       += $kg;
                $byMat[$vid]['total_cost']     += (float) $input->actual_cost;
                $byMat[$vid]['order_ids'][$order->id] = true;
            }

            $outKg = 0.0; $revenue = 0.0;
            foreach ($order->outputs as $output) {
                $v = $output->productVariant;
                if (!$v) continue;

                $w  = (float) $v->weight;
                $q  = (float) $output->actual_quantity;
                $kg = $w > 0 ? $q * $w : $q;
                $sp = (float) ($v->discount_selling_price ?? $v->selling_price ?? 0);
                $rev = $q * $sp;

                $outKg            += $kg;
                $revenue          += $rev;
                $agg['output_kg'] += $kg;
                $agg['revenue']   += $rev;
                $agg['defective_qty'] += (float) $output->defective_quantity;

                $vid = $v->id;
                $byProd[$vid] ??= [
                    'variant_id' => $vid, 'variant_name' => $v->name, 'sku' => $v->sku,
                    'category' => $v->product->category->name ?? 'Uncategorized',
                    'total_quantity' => 0.0, 'total_kg' => 0.0, 'total_revenue' => 0.0,
                    'order_ids' => [],
                ];
                $byProd[$vid]['total_quantity'] += $q;
                $byProd[$vid]['total_kg']       += $kg;
                $byProd[$vid]['total_revenue']  += $rev;
                $byProd[$vid]['order_ids'][$order->id] = true;
            }

            $batchCost          = (float) $order->total_input_cost;
            $agg['batch_cost'] += $batchCost;
            $agg['loss_kg']    += max(0, $inKg - $outKg);

            $mk = $order->created_at->format('Y-m');
            $monthly[$mk] ??= [
                'orders' => 0, 'completed' => 0,
                'input_kg' => 0.0, 'output_kg' => 0.0,
                'input_cost' => 0.0, 'output_cost' => 0.0,
            ];
            $monthly[$mk]['orders']++;
            if ($order->status === ProductionOrder::STATUS_COMPLETED) $monthly[$mk]['completed']++;
            $monthly[$mk]['input_kg']    += $inKg;
            $monthly[$mk]['output_kg']   += $outKg;
            $monthly[$mk]['input_cost']  += $batchCost;
            $monthly[$mk]['output_cost'] += $revenue;

            $lid = $order->location_id ?? 0;
            $byLoc[$lid] ??= [
                'location_id' => $lid,
                'location_name' => $order->location->name ?? 'Unknown',
                'orders' => 0, 'completed' => 0,
                'input_kg' => 0.0, 'output_kg' => 0.0,
                'total_cost' => 0.0, 'revenue' => 0.0,
            ];
            $byLoc[$lid]['orders']++;
            if ($order->status === ProductionOrder::STATUS_COMPLETED) $byLoc[$lid]['completed']++;
            $byLoc[$lid]['input_kg']   += $inKg;
            $byLoc[$lid]['output_kg']  += $outKg;
            $byLoc[$lid]['total_cost'] += $batchCost;
            $byLoc[$lid]['revenue']    += $revenue;
        }

        $n      = $orders->count();
        $profit = $agg['revenue'] - $agg['batch_cost'];

        $summary = [
            'total_orders'       => $n,
            'completed_orders'   => $completedCount,
            'in_progress_orders' => $inProgressCount,
            'draft_orders'       => $draftCount,
            'cancelled_orders'   => $cancelledCount,
            'completion_rate'    => $n > 0 ? ($completedCount / $n) * 100 : 0,

            'total_input_quantity'  => round($agg['input_kg'], 2),
            'total_output_quantity' => round($agg['output_kg'], 2),
            'total_loss_quantity'   => round($agg['loss_kg'], 2),
            'total_waste'           => round($agg['waste_qty'], 2),
            'total_defective'       => round($agg['defective_qty'], 2),

            'overall_yield' => $agg['input_kg'] > 0 ? ($agg['output_kg'] / $agg['input_kg']) * 100 : 0,
            'loss_rate'     => $agg['input_kg'] > 0 ? ($agg['loss_kg'] / $agg['input_kg']) * 100 : 0,

            'total_input_cost'  => round($agg['batch_cost'], 2),
            'total_output_cost' => round($agg['revenue'], 2),
            'total_cost'        => round($agg['batch_cost'], 2),
            'total_profit'      => round($profit, 2),
            'profit_margin'     => $agg['revenue'] > 0 ? ($profit / $agg['revenue']) * 100 : 0,
            'cost_efficiency'   => $agg['batch_cost'] > 0 ? ($agg['revenue'] / $agg['batch_cost']) * 100 : 0,

            'avg_cost_per_order'   => $n > 0 ? $agg['batch_cost'] / $n : 0,
            'avg_profit_per_order' => $n > 0 ? $profit / $n : 0,

            'total_quality_accepted'  => $agg['input_accepted'],
            'total_quality_rejected'  => $agg['input_rejected'],
            'quality_acceptance_rate' => $agg['input_total'] > 0
                ? ($agg['input_accepted'] / $agg['input_total']) * 100 : 0,

            'orders_with_payment'    => $agg['with_payment'],
            'orders_without_payment' => $agg['without_payment'],

            'avg_duration_hours' => $durN > 0 ? $durSum / $durN : 0,
        ];

        ksort($monthly);
        $monthlyTrends = [];
        foreach ($monthly as $ym => $b) {
            $monthlyTrends[] = [
                'month'       => Carbon::parse($ym . '-01')->format('M Y'),
                'orders'      => $b['orders'],
                'completed'   => $b['completed'],
                'input_kg'    => round($b['input_kg'], 2),
                'output_kg'   => round($b['output_kg'], 2),
                'input_cost'  => round($b['input_cost'], 2),
                'output_cost' => round($b['output_cost'], 2),
                'profit'      => round($b['output_cost'] - $b['input_cost'], 2),
                'yield'       => $b['input_kg'] > 0 ? round(($b['output_kg'] / $b['input_kg']) * 100, 2) : 0,
            ];
        }

        $topProducts = collect($byProd)->map(function ($p) {
            $count = count($p['order_ids']); unset($p['order_ids']);
            $p['total_kg']      = round($p['total_kg'], 2);
            $p['total_revenue'] = round($p['total_revenue'], 2);
            $p['avg_cost_per_kg'] = $p['total_kg'] > 0 ? $p['total_revenue'] / $p['total_kg'] : 0;
            $p['order_count']   = $count;
            return (object) $p;
        })->sortByDesc('total_kg')->values()->take(10);

        $topMaterials = collect($byMat)->map(function ($m) {
            $count = count($m['order_ids']); unset($m['order_ids']);
            $m['total_kg']   = round($m['total_kg'], 2);
            $m['total_cost'] = round($m['total_cost'], 2);
            $m['avg_cost_per_kg'] = $m['total_kg'] > 0 ? $m['total_cost'] / $m['total_kg'] : 0;
            $m['order_count'] = $count;
            return (object) $m;
        })->sortByDesc('total_kg')->values()->take(10);

        $statusBreakdown = collect([
            (object) ['status' => 'draft',       'label' => __('pagination.draft'),       'count' => $draftCount,       'color' => 'secondary'],
            (object) ['status' => 'in_progress', 'label' => __('pagination.in_progress'), 'count' => $inProgressCount,  'color' => 'warning'],
            (object) ['status' => 'completed',   'label' => __('pagination.completed'),   'count' => $completedCount,   'color' => 'success'],
            (object) ['status' => 'cancelled',   'label' => __('pagination.cancelled'),   'count' => $cancelledCount,   'color' => 'danger'],
        ])->filter(fn($x) => $x->count > 0)->values();

        $locationBreakdown = collect($byLoc)->map(function ($b) {
            return (object) [
                'location_id'   => $b['location_id'],
                'location_name' => $b['location_name'],
                'orders'        => $b['orders'],
                'completed'     => $b['completed'],
                'input_kg'      => round($b['input_kg'], 2),
                'output_kg'     => round($b['output_kg'], 2),
                'total_cost'    => round($b['total_cost'], 2),
                'revenue'       => round($b['revenue'], 2),
                'profit'        => round($b['revenue'] - $b['total_cost'], 2),
                'yield'         => $b['input_kg'] > 0 ? round(($b['output_kg'] / $b['input_kg']) * 100, 2) : 0,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'summary'           => $summary,
                'monthlyTrends'     => $monthlyTrends,
                'topProducts'       => $topProducts,
                'topMaterials'      => $topMaterials,
                'statusBreakdown'   => $statusBreakdown,
                'locationBreakdown' => $locationBreakdown,
            ],
        ]);
    }

    /**
     * Production Cost Analysis — PAGE SHELL.
     * Only filter options + initial filter state.
     */
    public function costAnalysis(Request $request)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = tenant_is_single_shop($tenantId);

        $startDate  = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate    = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId  = $request->get('variant_id');
        $status     = $request->get('status', 'all');
        $costType   = $request->get('cost_type', 'all');
        $perPage    = (int) $request->get('per_page', 15);

        $locations = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);

        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['product:id,name', 'unitMeasure:id,symbol,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'weight', 'weight_unit']);

        $statuses = [
            ['value' => 'all',                               'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_DRAFT,       'label' => __('pagination.draft')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
            ['value' => ProductionOrder::STATUS_COMPLETED,   'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_CANCELLED,   'label' => __('pagination.cancelled')],
        ];

        $costTypes = [
            ['value' => 'all',    'label' => __('pagination.all_costs')],
            ['value' => 'input',  'label' => __('pagination.input_costs')],
            ['value' => 'output', 'label' => __('pagination.output_costs')],
        ];

        return view('reports.production.cost-analysis', compact(
            'locations', 'variants', 'statuses', 'costTypes',
            'startDate', 'endDate', 'locationId', 'variantId', 'status', 'costType',
            'perPage', 'isSingleShop'
        ));
    }

    /**
     * Production Cost Analysis — DATA.
     * All rollups as JSON. Every weight→kg conversion via $variant->toKilograms()
     * so bags with weight (5kg, 10kg, 25kg, 50kg) are correctly normalized.
     */
    public function costAnalysisData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('production reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('production_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate  = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate    = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId  = $request->get('variant_id');
        $status     = $request->get('status', 'all');
        $perPage    = (int) $request->get('per_page', 15);
        $page       = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        // ─── Query ─────────────────────────────────────────────────────
        $query = ProductionOrder::with([
                'inputs.productVariant.product.category',
                'outputs.productVariant.product.category',
                'location:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        if ($locationId)                  $query->where('location_id', $locationId);
        if ($status && $status !== 'all') $query->where('status', $status);
        if ($variantId) {
            $query->where(function ($q) use ($variantId) {
                $q->whereHas('inputs',  fn($s) => $s->where('product_variant_id', $variantId))
                ->orWhereHas('outputs', fn($s) => $s->where('product_variant_id', $variantId));
            });
        }

        $orders = $query->get();

        // ─── Per-order rollups + flat rows for aggregation ────────────
        $orderRollups  = [];         // order_id => totals
        $inputRows     = [];         // each input line
        $outputRows    = [];         // each output line

        $totalInputCost = 0.0;
        $totalRevenue   = 0.0;
        $totalInputKg   = 0.0;
        $totalOutputKg  = 0.0;

        foreach ($orders as $order) {
            $orderInputCost = (float) $order->total_input_cost;
            $orderInKg = 0.0;
            $orderOutKg = 0.0;
            $orderRevenue = 0.0;

            foreach ($order->inputs as $input) {
                $variant = $input->productVariant;
                if (!$variant) continue;

                $qty = (float) $input->actual_quantity;
                $kg  = $variant->toKilograms($qty);              // ← weight-aware
                $cost = (float) $input->actual_cost;

                $orderInKg += $kg;

                $inputRows[] = [
                    'order_id'     => $order->id,
                    'order_number' => $order->production_number,
                    'variant_id'   => $variant->id,
                    'variant_name' => $variant->name,
                    'variant_sku'  => $variant->sku,
                    'category'     => $variant->product->category->name ?? 'Uncategorized',
                    'quantity'     => $qty,
                    'weight'       => (float) $variant->weight,
                    'total_kg'     => $kg,
                    'cost'         => $cost,
                ];
            }

            foreach ($order->outputs as $output) {
                $variant = $output->productVariant;
                if (!$variant) continue;

                $qty = (float) $output->actual_quantity;
                $kg  = $variant->toKilograms($qty);              // ← weight-aware
                $sell = (float) ($variant->discount_selling_price ?? $variant->selling_price ?? 0);
                $revenue = $qty * $sell;

                $orderOutKg  += $kg;
                $orderRevenue += $revenue;

                $outputRows[] = [
                    'order_id'      => $order->id,
                    'order_number'  => $order->production_number,
                    'variant_id'    => $variant->id,
                    'variant_name'  => $variant->name,
                    'variant_sku'   => $variant->sku,
                    'category'      => $variant->product->category->name ?? 'Uncategorized',
                    'quantity'      => $qty,
                    'weight'        => (float) $variant->weight,
                    'total_kg'      => $kg,
                    'selling_price' => $sell,
                    'revenue'       => $revenue,
                ];
            }

            $orderRollups[$order->id] = [
                'input_kg'   => $orderInKg,
                'output_kg'  => $orderOutKg,
                'input_cost' => $orderInputCost,
                'revenue'    => $orderRevenue,
            ];

            $totalInputCost += $orderInputCost;
            $totalRevenue   += $orderRevenue;
            $totalInputKg   += $orderInKg;
            $totalOutputKg  += $orderOutKg;
        }

        $totalProfit = $totalRevenue - $totalInputCost;
        $totalLossKg = max(0, $totalInputKg - $totalOutputKg);
        $overallYield = $totalInputKg > 0 ? ($totalOutputKg / $totalInputKg) * 100 : 0;
        $n = $orders->count();

        $costSummary = [
            'total_input_cost'         => round($totalInputCost, 2),
            'avg_input_cost_per_order' => $n > 0 ? round($totalInputCost / $n, 2) : 0,
            'min_input_cost'           => round($orders->min('total_input_cost') ?? 0, 2),
            'max_input_cost'           => round($orders->max('total_input_cost') ?? 0, 2),
            'total_input_kg'           => round($totalInputKg, 2),

            'total_output_cost'         => round($totalRevenue, 2),
            'avg_output_cost_per_order' => $n > 0 ? round($totalRevenue / $n, 2) : 0,
            'total_output_kg'           => round($totalOutputKg, 2),
            'total_loss_kg'             => round($totalLossKg, 2),

            'total_cost'         => round($totalInputCost, 2),
            'avg_cost_per_order' => $n > 0 ? round($totalInputCost / $n, 2) : 0,

            'total_profit'         => round($totalProfit, 2),
            'avg_profit_per_order' => $n > 0 ? round($totalProfit / $n, 2) : 0,

            'cost_efficiency' => $totalInputCost > 0 ? round(($totalRevenue / $totalInputCost) * 100, 2) : 0,
            'profit_margin'   => $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 2) : 0,
            'overall_yield'   => round($overallYield, 2),
            'loss_rate'       => $totalInputKg > 0 ? round(($totalLossKg / $totalInputKg) * 100, 2) : 0,

            'cost_per_kg_input'  => $totalInputKg  > 0 ? round($totalInputCost / $totalInputKg, 2)  : 0,
            'cost_per_kg_output' => $totalOutputKg > 0 ? round($totalInputCost / $totalOutputKg, 2) : 0,
            'revenue_per_kg'     => $totalOutputKg > 0 ? round($totalRevenue / $totalOutputKg, 2)   : 0,
        ];

        // ─── Input cost by category ─────────────────────────────────
        $inputByCat = [];
        foreach ($inputRows as $r) {
            $c = $r['category'];
            $inputByCat[$c] ??= ['category' => $c, 'total_cost' => 0.0, 'total_kg' => 0.0,
                                'total_quantity' => 0.0, 'item_count' => 0];
            $inputByCat[$c]['total_cost']     += $r['cost'];
            $inputByCat[$c]['total_kg']       += $r['total_kg'];
            $inputByCat[$c]['total_quantity'] += $r['quantity'];
            $inputByCat[$c]['item_count']++;
        }
        $categoryInputCosts = collect($inputByCat)->map(function ($v) {
            return (object) [
                'category'          => $v['category'],
                'total_cost'        => round($v['total_cost'], 2),
                'total_kg'          => round($v['total_kg'], 2),
                'total_quantity'    => round($v['total_quantity'], 2),
                'avg_cost_per_unit' => $v['total_quantity'] > 0 ? round($v['total_cost'] / $v['total_quantity'], 2) : 0,
                'avg_cost_per_kg'   => $v['total_kg']       > 0 ? round($v['total_cost'] / $v['total_kg'], 2) : 0,
                'item_count'        => $v['item_count'],
            ];
        })->sortByDesc('total_cost')->values();

        // ─── Output revenue by category ────────────────────────────
        $outputByCat = [];
        foreach ($outputRows as $r) {
            $c = $r['category'];
            $outputByCat[$c] ??= ['category' => $c, 'total_cost' => 0.0, 'total_kg' => 0.0,
                                'total_quantity' => 0.0, 'item_count' => 0];
            $outputByCat[$c]['total_cost']     += $r['revenue'];
            $outputByCat[$c]['total_kg']       += $r['total_kg'];
            $outputByCat[$c]['total_quantity'] += $r['quantity'];
            $outputByCat[$c]['item_count']++;
        }
        $categoryOutputCosts = collect($outputByCat)->map(function ($v) {
            return (object) [
                'category'          => $v['category'],
                'total_cost'        => round($v['total_cost'], 2),
                'total_kg'          => round($v['total_kg'], 2),
                'total_quantity'    => round($v['total_quantity'], 2),
                'avg_cost_per_unit' => $v['total_quantity'] > 0 ? round($v['total_cost'] / $v['total_quantity'], 2) : 0,
                'avg_cost_per_kg'   => $v['total_kg']       > 0 ? round($v['total_cost'] / $v['total_kg'], 2) : 0,
                'item_count'        => $v['item_count'],
            ];
        })->sortByDesc('total_cost')->values();

        // ─── Cost per unit / kg per variant ────────────────────────
        $variantAcc = [];
        foreach ($outputRows as $r) {
            $roll = $orderRollups[$r['order_id']] ?? null;
            $allocated = ($roll && $roll['output_kg'] > 0)
                ? ($r['total_kg'] / $roll['output_kg']) * $roll['input_cost']
                : 0;

            $vid = $r['variant_id'];
            $variantAcc[$vid] ??= [
                'variant_id' => $vid, 'variant_name' => $r['variant_name'],
                'variant_sku' => $r['variant_sku'], 'category' => $r['category'],
                'weight' => $r['weight'],
                'total_quantity' => 0.0, 'total_kg' => 0.0,
                'allocated_cost' => 0.0, 'revenue' => 0.0,
                'selling_price_sum' => 0.0, 'selling_price_count' => 0,
                'order_ids' => [],
            ];
            $variantAcc[$vid]['total_quantity'] += $r['quantity'];
            $variantAcc[$vid]['total_kg']       += $r['total_kg'];
            $variantAcc[$vid]['allocated_cost'] += $allocated;
            $variantAcc[$vid]['revenue']        += $r['revenue'];
            $variantAcc[$vid]['selling_price_sum']   += $r['selling_price'];
            $variantAcc[$vid]['selling_price_count'] += 1;
            $variantAcc[$vid]['order_ids'][$r['order_number']] = true;
        }

        $costPerUnitSummary = collect($variantAcc)->map(function ($v) {
            $qty = $v['total_quantity']; $kg = $v['total_kg'];
            $cost = $v['allocated_cost']; $rev = $v['revenue'];
            return (object) [
                'variant_id'          => $v['variant_id'],
                'variant_name'        => $v['variant_name'],
                'variant_sku'         => $v['variant_sku'],
                'category'            => $v['category'],
                'weight'              => $v['weight'],
                'total_quantity'      => round($qty, 2),
                'total_kg'            => round($kg, 2),
                'allocated_cost'      => round($cost, 2),
                'revenue'             => round($rev, 2),
                'avg_cost_per_unit'   => $qty > 0 ? round($cost / $qty, 2) : 0,
                'avg_cost_per_kg'     => $kg  > 0 ? round($cost / $kg, 2)  : 0,
                'avg_selling_price'   => $v['selling_price_count'] > 0
                    ? round($v['selling_price_sum'] / $v['selling_price_count'], 2) : 0,
                'avg_revenue_per_kg'  => $kg  > 0 ? round($rev / $kg, 2) : 0,
                'avg_profit_per_unit' => $qty > 0 ? round(($rev - $cost) / $qty, 2) : 0,
                'avg_profit_per_kg'   => $kg  > 0 ? round(($rev - $cost) / $kg, 2)  : 0,
                'avg_profit_margin'   => $rev > 0 ? round((($rev - $cost) / $rev) * 100, 2) : 0,
                'order_count'         => count($v['order_ids']),
            ];
        })->sortByDesc('avg_profit_margin')->values();

        // ─── Monthly trends ────────────────────────────────────────
        $monthlyBuckets = [];
        foreach ($orders as $order) {
            $mk = $order->created_at->format('Y-m');
            $r  = $orderRollups[$order->id];
            $monthlyBuckets[$mk] ??= ['orders' => 0, 'input_cost' => 0.0, 'revenue' => 0.0,
                                    'input_kg' => 0.0, 'output_kg' => 0.0];
            $monthlyBuckets[$mk]['orders']++;
            $monthlyBuckets[$mk]['input_cost'] += $r['input_cost'];
            $monthlyBuckets[$mk]['revenue']    += $r['revenue'];
            $monthlyBuckets[$mk]['input_kg']   += $r['input_kg'];
            $monthlyBuckets[$mk]['output_kg']  += $r['output_kg'];
        }
        ksort($monthlyBuckets);
        $monthlyCostTrends = [];
        foreach ($monthlyBuckets as $ym => $b) {
            $monthlyCostTrends[] = [
                'month'              => Carbon::parse($ym . '-01')->format('M Y'),
                'input_cost'         => round($b['input_cost'], 2),
                'output_cost'        => round($b['revenue'], 2),
                'total_cost'         => round($b['input_cost'], 2),
                'profit'             => round($b['revenue'] - $b['input_cost'], 2),
                'orders'             => $b['orders'],
                'input_kg'           => round($b['input_kg'], 2),
                'output_kg'          => round($b['output_kg'], 2),
                'yield'              => $b['input_kg'] > 0 ? round(($b['output_kg'] / $b['input_kg']) * 100, 2) : 0,
                'avg_cost_per_order' => $b['orders'] > 0 ? round($b['input_cost'] / $b['orders'], 2) : 0,
            ];
        }

        // ─── Cost by status ────────────────────────────────────────
        $costByStatus = collect([
            'draft' => (object)[
                'status' => __('pagination.draft'),
                'orders' => $orders->where('status', ProductionOrder::STATUS_DRAFT)->count(),
                'total_cost' => round($orders->where('status', ProductionOrder::STATUS_DRAFT)->sum('total_input_cost'), 2),
                'color' => 'secondary',
            ],
            'in_progress' => (object)[
                'status' => __('pagination.in_progress'),
                'orders' => $orders->where('status', ProductionOrder::STATUS_IN_PROGRESS)->count(),
                'total_cost' => round($orders->where('status', ProductionOrder::STATUS_IN_PROGRESS)->sum('total_input_cost'), 2),
                'color' => 'warning',
            ],
            'completed' => (object)[
                'status' => __('pagination.completed'),
                'orders' => $orders->where('status', ProductionOrder::STATUS_COMPLETED)->count(),
                'total_cost' => round($orders->where('status', ProductionOrder::STATUS_COMPLETED)->sum('total_input_cost'), 2),
                'color' => 'success',
            ],
            'cancelled' => (object)[
                'status' => __('pagination.cancelled'),
                'orders' => $orders->where('status', ProductionOrder::STATUS_CANCELLED)->count(),
                'total_cost' => round($orders->where('status', ProductionOrder::STATUS_CANCELLED)->sum('total_input_cost'), 2),
                'color' => 'danger',
            ],
        ])->filter(fn($x) => $x->orders > 0)->values();

        // ─── Server-side pagination for cost-per-unit table ────────
        $totalRows = $costPerUnitSummary->count();
        $lastPage  = max(1, (int) ceil($totalRows / $perPage));
        $page      = min($page, $lastPage);
        $offset    = ($page - 1) * $perPage;
        $paginatedCostPerUnit = $costPerUnitSummary->slice($offset, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'costSummary'          => $costSummary,
                'categoryInputCosts'   => $categoryInputCosts,
                'categoryOutputCosts'  => $categoryOutputCosts,
                'costPerUnit'          => $paginatedCostPerUnit,
                'monthlyCostTrends'    => $monthlyCostTrends,
                'costByStatus'         => $costByStatus,
                'meta' => [
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                    'orders'     => $n,
                ],
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

    public function efficiency(Request $request)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = tenant_is_single_shop($tenantId);

        $startDate  = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate    = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId  = $request->get('variant_id');
        $status     = $request->get('status', 'completed');
        $perPage    = (int) $request->get('per_page', 15);

        $locations = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);

        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['product:id,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'weight']);

        $statuses = [
            ['value' => 'all',                               'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_COMPLETED,   'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
            ['value' => ProductionOrder::STATUS_DRAFT,       'label' => __('pagination.draft')],
            ['value' => ProductionOrder::STATUS_CANCELLED,   'label' => __('pagination.cancelled')],
        ];

        return view('reports.production.efficiency', compact(
            'locations', 'variants', 'statuses',
            'startDate', 'endDate', 'locationId', 'variantId', 'status', 'perPage', 'isSingleShop'
        ));
    }

    public function efficiencyData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('production reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('production_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate  = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate    = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId  = $request->get('variant_id');
        $status     = $request->get('status', 'completed');
        $perPage    = (int) $request->get('per_page', 15);
        $page       = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $query = ProductionOrder::with([
                'inputs.productVariant.product.category',
                'outputs.productVariant.product.category',
                'location:id,name',
                'createdBy:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        if ($locationId)                  $query->where('location_id', $locationId);
        if ($status && $status !== 'all') $query->where('status', $status);
        if ($variantId) {
            $query->where(function ($q) use ($variantId) {
                $q->whereHas('inputs',  fn($s) => $s->where('product_variant_id', $variantId))
                ->orWhereHas('outputs', fn($s) => $s->where('product_variant_id', $variantId));
            });
        }

        $orders           = $query->get();
        $completedOrders  = $orders->where('status', ProductionOrder::STATUS_COMPLETED);
        $inProgressOrders = $orders->where('status', ProductionOrder::STATUS_IN_PROGRESS);

        // ─── Per-order rollup (KG-normalized via $variant->toKilograms()) ──
        $orderRollups = [];

        foreach ($orders as $order) {
            $inKg = 0.0; $outKg = 0.0;
            $revenue = 0.0;
            $defectiveKg = 0.0;
            $wasteKg     = 0.0;

            foreach ($order->inputs as $input) {
                $v = $input->productVariant;
                if (!$v) continue;
                $qty = (float) $input->actual_quantity;
                $kg  = $v->toKilograms($qty);       // ← weight-aware
                $inKg += $kg;

                $wq = (float) $input->waste_quantity;
                $wasteKg += $v->toKilograms($wq);
            }

            foreach ($order->outputs as $output) {
                $v = $output->productVariant;
                if (!$v) continue;
                $qty = (float) $output->actual_quantity;
                $kg  = $v->toKilograms($qty);
                $sell = (float) ($v->discount_selling_price ?? $v->selling_price ?? 0);

                $outKg   += $kg;
                $revenue += $qty * $sell;

                $dq = (float) $output->defective_quantity;
                $defectiveKg += $v->toKilograms($dq);
            }

            $duration = ($order->started_at && $order->completed_at)
                ? $order->started_at->diffInMinutes($order->completed_at) / 60
                : 0;

            $orderRollups[$order->id] = [
                'input_kg'     => $inKg,
                'output_kg'    => $outKg,
                'input_cost'   => (float) $order->total_input_cost,
                'revenue'      => $revenue,
                'profit'       => $revenue - (float) $order->total_input_cost,
                'yield'        => $inKg > 0 ? ($outKg / $inKg) * 100 : 0,
                'loss_kg'      => max(0, $inKg - $outKg),
                'defective_kg' => $defectiveKg,
                'waste_kg'     => $wasteKg,
                'duration'     => $duration,
            ];
        }

        // ─── Aggregate totals ─────────────────────────────────────
        $totalInputKg = $totalOutputKg = 0.0;
        $totalInputCost = $totalRevenue = 0.0;
        $totalDefKg = $totalWasteKg = $totalDur = 0.0;

        foreach ($orderRollups as $r) {
            $totalInputKg    += $r['input_kg'];
            $totalOutputKg   += $r['output_kg'];
            $totalInputCost  += $r['input_cost'];
            $totalRevenue    += $r['revenue'];
            $totalDefKg      += $r['defective_kg'];
            $totalWasteKg    += $r['waste_kg'];
            $totalDur        += $r['duration'];
        }

        $totalProfit = $totalRevenue - $totalInputCost;
        $overallYield = $totalInputKg > 0 ? ($totalOutputKg / $totalInputKg) * 100 : 0;
        $costEff      = $totalInputCost > 0 ? ($totalRevenue / $totalInputCost) * 100 : 0;
        $qualityRate  = $totalOutputKg > 0 ? (($totalOutputKg - $totalDefKg) / $totalOutputKg) * 100 : 0;
        $wasteRate    = $totalInputKg > 0 ? ($totalWasteKg / $totalInputKg) * 100 : 0;
        $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
        $avgDuration  = $completedOrders->count() > 0 ? $totalDur / $completedOrders->count() : 0;

        $efficiencySummary = [
            'total_orders'            => $orders->count(),
            'completed_orders'        => $completedOrders->count(),
            'in_progress_orders'      => $inProgressOrders->count(),
            'overall_yield'           => round($overallYield, 2),
            'overall_cost_efficiency' => round($costEff, 2),
            'avg_duration_hours'      => round($avgDuration, 2),
            'quality_rate'            => round($qualityRate, 2),
            'waste_rate'              => round($wasteRate, 2),
            'profit_margin'           => round($profitMargin, 2),
            'total_input_kg'          => round($totalInputKg, 2),
            'total_output_kg'         => round($totalOutputKg, 2),
            'total_loss_kg'           => round(max(0, $totalInputKg - $totalOutputKg), 2),
            'loss_rate'               => $totalInputKg > 0
                ? round((max(0, $totalInputKg - $totalOutputKg) / $totalInputKg) * 100, 2) : 0,
            'total_waste_kg'          => round($totalWasteKg, 2),
            'total_defective_kg'      => round($totalDefKg, 2),
            'total_profit'            => round($totalProfit, 2),
            'total_input_cost'        => round($totalInputCost, 2),
            'total_output_cost'       => round($totalRevenue, 2),
            'total_cost'              => round($totalInputCost, 2),
        ];

        // ─── Efficiency by order (paginated server-side) ──────────
        $efficiencyByOrder = $orders->map(function ($order) use ($orderRollups) {
            $r = $orderRollups[$order->id];
            $costEff = $r['input_cost'] > 0 ? ($r['revenue'] / $r['input_cost']) * 100 : 0;
            $quality = $r['output_kg'] > 0
                ? (($r['output_kg'] - $r['defective_kg']) / $r['output_kg']) * 100 : 0;
            $wastePct = $r['input_kg'] > 0 ? ($r['waste_kg'] / $r['input_kg']) * 100 : 0;
            $margin   = $r['revenue'] > 0 ? ($r['profit'] / $r['revenue']) * 100 : 0;

            return (object) [
                'id'                => $order->id,
                'production_number' => $order->production_number,
                'status_label'      => $order->status_label,
                'status_badge'      => $order->status_badge,
                'location'          => $order->location->name ?? '-',
                'created_at'        => $order->created_at->format('Y-m-d'),
                'input_kg'          => round($r['input_kg'], 2),
                'output_kg'         => round($r['output_kg'], 2),
                'loss_kg'           => round($r['loss_kg'], 2),
                'profit'            => round($r['profit'], 2),
                'yield'             => round($r['yield'], 2),
                'cost_efficiency'   => round($costEff, 2),
                'quality_rate'      => round($quality, 2),
                'waste_rate'        => round($wastePct, 2),
                'profit_margin'     => round($margin, 2),
                'duration_hours'    => round($r['duration'], 2),
                'created_by'        => $order->createdBy->name ?? '-',
            ];
        });

        $totalRows = $efficiencyByOrder->count();
        $lastPage  = max(1, (int) ceil($totalRows / $perPage));
        $page      = min($page, $lastPage);
        $offset    = ($page - 1) * $perPage;
        $paginated = $efficiencyByOrder->slice($offset, $perPage)->values();

        // ─── Monthly trends ───────────────────────────────────────
        $monthlyBuckets = [];
        foreach ($completedOrders as $order) {
            $mk = $order->completed_at
                ? $order->completed_at->format('Y-m')
                : $order->created_at->format('Y-m');
            $r = $orderRollups[$order->id];
            $monthlyBuckets[$mk] ??= [
                'orders' => 0, 'input_kg' => 0.0, 'output_kg' => 0.0,
                'input_cost' => 0.0, 'revenue' => 0.0,
                'def_kg' => 0.0, 'waste_kg' => 0.0, 'duration' => 0.0,
            ];
            $monthlyBuckets[$mk]['orders']++;
            $monthlyBuckets[$mk]['input_kg']   += $r['input_kg'];
            $monthlyBuckets[$mk]['output_kg']  += $r['output_kg'];
            $monthlyBuckets[$mk]['input_cost'] += $r['input_cost'];
            $monthlyBuckets[$mk]['revenue']    += $r['revenue'];
            $monthlyBuckets[$mk]['def_kg']     += $r['defective_kg'];
            $monthlyBuckets[$mk]['waste_kg']   += $r['waste_kg'];
            $monthlyBuckets[$mk]['duration']   += $r['duration'];
        }
        ksort($monthlyBuckets);
        $monthlyEfficiency = [];
        foreach ($monthlyBuckets as $ym => $b) {
            $inKg = $b['input_kg']; $outKg = $b['output_kg'];
            $inCost = $b['input_cost']; $rev = $b['revenue'];
            $defKg = $b['def_kg']; $wKg = $b['waste_kg'];
            $monthlyEfficiency[] = [
                'month'           => Carbon::parse($ym . '-01')->format('M Y'),
                'orders'          => $b['orders'],
                'yield'           => $inKg > 0 ? round(($outKg / $inKg) * 100, 2) : 0,
                'cost_efficiency' => $inCost > 0 ? round(($rev / $inCost) * 100, 2) : 0,
                'quality_rate'    => $outKg > 0 ? round((($outKg - $defKg) / $outKg) * 100, 2) : 0,
                'waste_rate'      => $inKg > 0 ? round(($wKg / $inKg) * 100, 2) : 0,
                'profit_margin'   => $rev > 0 ? round((($rev - $inCost) / $rev) * 100, 2) : 0,
                'avg_duration'    => $b['orders'] > 0 ? round($b['duration'] / $b['orders'], 2) : 0,
                'profit'          => round($rev - $inCost, 2),
                'loss_kg'         => round(max(0, $inKg - $outKg), 2),
            ];
        }

        // ─── Efficiency by location ───────────────────────────────
        $locBuckets = [];
        foreach ($completedOrders as $order) {
            $lid = $order->location_id ?? 0;
            $r = $orderRollups[$order->id];
            $locBuckets[$lid] ??= [
                'location_name' => $order->location->name ?? 'Unknown',
                'orders' => 0, 'input_kg' => 0.0, 'output_kg' => 0.0,
                'input_cost' => 0.0, 'revenue' => 0.0,
                'def_kg' => 0.0, 'waste_kg' => 0.0, 'duration' => 0.0,
            ];
            $locBuckets[$lid]['orders']++;
            $locBuckets[$lid]['input_kg']   += $r['input_kg'];
            $locBuckets[$lid]['output_kg']  += $r['output_kg'];
            $locBuckets[$lid]['input_cost'] += $r['input_cost'];
            $locBuckets[$lid]['revenue']    += $r['revenue'];
            $locBuckets[$lid]['def_kg']     += $r['defective_kg'];
            $locBuckets[$lid]['waste_kg']   += $r['waste_kg'];
            $locBuckets[$lid]['duration']   += $r['duration'];
        }
        $efficiencyByLocation = [];
        foreach ($locBuckets as $b) {
            $inKg = $b['input_kg']; $outKg = $b['output_kg'];
            $inCost = $b['input_cost']; $rev = $b['revenue'];
            $defKg = $b['def_kg']; $wKg = $b['waste_kg'];
            $efficiencyByLocation[] = [
                'location_name'   => $b['location_name'],
                'orders'          => $b['orders'],
                'yield'           => $inKg > 0 ? round(($outKg / $inKg) * 100, 2) : 0,
                'cost_efficiency' => $inCost > 0 ? round(($rev / $inCost) * 100, 2) : 0,
                'quality_rate'    => $outKg > 0 ? round((($outKg - $defKg) / $outKg) * 100, 2) : 0,
                'waste_rate'      => $inKg > 0 ? round(($wKg / $inKg) * 100, 2) : 0,
                'profit_margin'   => $rev > 0 ? round((($rev - $inCost) / $rev) * 100, 2) : 0,
                'avg_duration'    => $b['orders'] > 0 ? round($b['duration'] / $b['orders'], 2) : 0,
                'profit'          => round($rev - $inCost, 2),
            ];
        }

        // ─── Product efficiency (top 10 by profit) ────────────────
        $variantAcc = [];
        foreach ($completedOrders as $order) {
            $roll = $orderRollups[$order->id] ?? null;
            if (!$roll || $roll['output_kg'] <= 0) continue;

            foreach ($order->outputs as $output) {
                $v = $output->productVariant;
                if (!$v) continue;

                $qty = (float) $output->actual_quantity;
                $kg  = $v->toKilograms($qty);
                $defKg = $v->toKilograms((float) $output->defective_quantity);
                $sell = (float) ($v->discount_selling_price ?? $v->selling_price ?? 0);
                $rev  = $qty * $sell;

                $allocatedCost = ($kg / $roll['output_kg']) * $roll['input_cost'];

                $vid = $v->id;
                $variantAcc[$vid] ??= [
                    'variant_id' => $vid,
                    'variant_name' => $v->name,
                    'variant_sku' => $v->sku,
                    'category' => $v->product->category->name ?? 'Uncategorized',
                    'total_quantity' => 0.0, 'total_kg' => 0.0,
                    'def_kg' => 0.0, 'revenue' => 0.0, 'cost' => 0.0,
                ];
                $variantAcc[$vid]['total_quantity'] += $qty;
                $variantAcc[$vid]['total_kg']       += $kg;
                $variantAcc[$vid]['def_kg']         += $defKg;
                $variantAcc[$vid]['revenue']        += $rev;
                $variantAcc[$vid]['cost']           += $allocatedCost;
            }
        }
        $productEfficiencySummary = collect($variantAcc)->map(function ($v) {
            $kg = $v['total_kg']; $qty = $v['total_quantity'];
            $defKg = $v['def_kg']; $rev = $v['revenue']; $cost = $v['cost'];
            $profit = $rev - $cost;
            return (object) [
                'variant_name'    => $v['variant_name'],
                'variant_sku'     => $v['variant_sku'],
                'category'        => $v['category'],
                'total_quantity'  => round($qty, 2),
                'total_kg'        => round($kg, 2),
                'total_cost'      => round($cost, 2),
                'total_revenue'   => round($rev, 2),
                'total_profit'    => round($profit, 2),
                'total_defective' => round($defKg, 2),
                'profit_margin'   => $rev > 0 ? round(($profit / $rev) * 100, 2) : 0,
                'quality_rate'    => $kg > 0 ? round((($kg - $defKg) / $kg) * 100, 2) : 0,
                'cost_per_kg'     => $kg > 0 ? round($cost / $kg, 2) : 0,
                'profit_per_kg'   => $kg > 0 ? round($profit / $kg, 2) : 0,
            ];
        })->sortByDesc('total_profit')->values()->take(10);

        return response()->json([
            'success' => true,
            'data' => [
                'efficiencySummary'        => $efficiencySummary,
                'monthlyEfficiency'        => $monthlyEfficiency,
                'efficiencyByLocation'     => $efficiencyByLocation,
                'productEfficiencySummary' => $productEfficiencySummary,
                'efficiencyByOrder'        => $paginated,
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $totalRows,
                    'from'         => $totalRows > 0 ? $offset + 1 : 0,
                    'to'           => min($offset + $perPage, $totalRows),
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
     * Production Inventory Impact Report
     * KG-normalized view of how production orders move inventory
     */
    public function inventoryImpact(Request $request)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = tenant_is_single_shop($tenantId);

        $startDate  = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate    = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId  = $request->get('variant_id');
        $status     = $request->get('status', 'completed');
        $perPage    = (int) $request->get('per_page', 15);

        $locations = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);

        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['product:id,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'weight']);

        $statuses = [
            ['value' => 'all',                               'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_COMPLETED,   'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
            ['value' => ProductionOrder::STATUS_DRAFT,       'label' => __('pagination.draft')],
            ['value' => ProductionOrder::STATUS_CANCELLED,   'label' => __('pagination.cancelled')],
        ];

        return view('reports.production.inventory-impact', compact(
            'locations', 'variants', 'statuses',
            'startDate', 'endDate', 'locationId', 'variantId', 'status', 'perPage', 'isSingleShop'
        ));
    }

    public function inventoryImpactData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('production reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('production_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate  = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate    = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId  = $request->get('variant_id');
        $status     = $request->get('status', 'completed');
        $perPage    = (int) $request->get('per_page', 15);
        $page       = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $query = ProductionOrder::with([
                'inputs.productVariant.product.category',
                'outputs.productVariant.product.category',
                'location:id,name',
                'createdBy:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        if ($locationId)                  $query->where('location_id', $locationId);
        if ($status && $status !== 'all') $query->where('status', $status);
        if ($variantId) {
            $query->where(function ($q) use ($variantId) {
                $q->whereHas('inputs',  fn($s) => $s->where('product_variant_id', $variantId))
                ->orWhereHas('outputs', fn($s) => $s->where('product_variant_id', $variantId));
            });
        }

        $orders           = $query->get();
        $completedOrders  = $orders->where('status', ProductionOrder::STATUS_COMPLETED);

        // ─── Per-order + per-row rollups ─────────────────────────
        $orderRollups = [];
        $consumedRows = collect();
        $producedRows = collect();

        foreach ($orders as $order) {
            $inKg = 0.0; $outKg = 0.0;
            $revenue = 0.0;
            $defKg = 0.0; $wasteKg = 0.0;

            foreach ($order->inputs as $input) {
                $v = $input->productVariant;
                if (!$v) continue;

                $qty = (float) $input->actual_quantity;
                $kg  = $v->toKilograms($qty);            // ← weight-aware
                $inKg += $kg;

                $wq = (float) $input->waste_quantity;
                $wasteKg += $v->toKilograms($wq);

                $consumedRows->push((object) [
                    'order_id'     => $order->id,
                    'variant_id'   => $v->id,
                    'variant_name' => $v->name,
                    'variant_sku'  => $v->sku,
                    'weight'       => (float) $v->weight,
                    'category'     => $v->product->category->name ?? 'Uncategorized',
                    'quantity'     => $qty,
                    'total_kg'     => $kg,
                    'cost'         => (float) $input->actual_cost,
                ]);
            }

            foreach ($order->outputs as $output) {
                $v = $output->productVariant;
                if (!$v) continue;

                $qty  = (float) $output->actual_quantity;
                $kg   = $v->toKilograms($qty);
                $sell = (float) ($v->selling_price ?? 0);

                $outKg   += $kg;
                $revenue += $qty * $sell;

                $dq = (float) $output->defective_quantity;
                $defKg += $v->toKilograms($dq);

                $producedRows->push((object) [
                    'order_id'     => $order->id,
                    'variant_id'   => $v->id,
                    'variant_name' => $v->name,
                    'variant_sku'  => $v->sku,
                    'weight'       => (float) $v->weight,
                    'category'     => $v->product->category->name ?? 'Uncategorized',
                    'quantity'     => $qty,
                    'total_kg'     => $kg,
                    'revenue'      => $qty * $sell,
                ]);
            }

            $orderRollups[$order->id] = [
                'input_kg'   => $inKg,
                'output_kg'  => $outKg,
                'input_cost' => (float) $order->total_input_cost,
                'revenue'    => $revenue,
                'net_kg'     => $outKg - $inKg,
                'net_value'  => $revenue - (float) $order->total_input_cost,
                'defective'  => $defKg,
                'waste'      => $wasteKg,
            ];
        }

        // ─── Totals ──────────────────────────────────────────────
        $totalInKg = $totalOutKg = 0.0;
        $totalInVal = $totalRev = 0.0;
        $totalDefKg = $totalWasteKg = 0.0;
        foreach ($orderRollups as $r) {
            $totalInKg   += $r['input_kg'];
            $totalOutKg  += $r['output_kg'];
            $totalInVal  += $r['input_cost'];
            $totalRev    += $r['revenue'];
            $totalDefKg  += $r['defective'];
            $totalWasteKg+= $r['waste'];
        }

        $netKg    = $totalOutKg - $totalInKg;
        $netValue = $totalRev - $totalInVal;

        // ─── Consumed / produced by variant ─────────────────────
        $consumedSummary = $consumedRows->groupBy('variant_id')->map(function ($items) {
            $first = $items->first();
            $kg = $items->sum('total_kg');
            $cost = $items->sum('cost');
            return (object) [
                'variant_id'     => $first->variant_id,
                'variant_name'   => $first->variant_name,
                'variant_sku'    => $first->variant_sku,
                'category'       => $first->category,
                'weight'         => $first->weight,
                'total_quantity' => $items->sum('quantity'),
                'total_kg'       => round($kg, 2),
                'total_cost'     => round($cost, 2),
                'order_count'    => $items->pluck('order_id')->unique()->count(),
            ];
        })->sortByDesc('total_kg')->values();

        $producedSummary = $producedRows->groupBy('variant_id')->map(function ($items) {
            $first = $items->first();
            return (object) [
                'variant_id'      => $first->variant_id,
                'variant_name'    => $first->variant_name,
                'variant_sku'     => $first->variant_sku,
                'category'        => $first->category,
                'weight'          => $first->weight,
                'total_quantity'  => $items->sum('quantity'),
                'total_kg'        => round($items->sum('total_kg'), 2),
                'total_revenue'   => round($items->sum('revenue'), 2),
                'order_count'     => $items->pluck('order_id')->unique()->count(),
            ];
        })->sortByDesc('total_kg')->values();

        // ─── Net impact by variant ──────────────────────────────
        $allVids = $consumedRows->pluck('variant_id')->merge($producedRows->pluck('variant_id'))->unique()->filter();
        $netImpact = collect();
        foreach ($allVids as $vid) {
            $con = $consumedRows->where('variant_id', $vid);
            $pro = $producedRows->where('variant_id', $vid);
            $conKg = (float) $con->sum('total_kg');
            $proKg = (float) $pro->sum('total_kg');
            $conVal = (float) $con->sum('cost');
            $proVal = (float) $pro->sum('revenue');
            $src = $con->first() ?: $pro->first();
            if (!$src) continue;

            $nKg = $proKg - $conKg;
            $nVal = $proVal - $conVal;
            $netImpact->push((object) [
                'variant_id'        => $vid,
                'variant_name'      => $src->variant_name,
                'variant_sku'       => $src->variant_sku,
                'category'          => $src->category,
                'consumed_quantity' => round($conKg, 2),
                'produced_quantity' => round($proKg, 2),
                'net_quantity'      => round($nKg, 2),
                'consumed_cost'     => round($conVal, 2),
                'produced_cost'     => round($proVal, 2),
                'net_value'         => round($nVal, 2),
                'order_count'       => $con->pluck('order_id')->merge($pro->pluck('order_id'))->unique()->count(),
                'impact_type'       => $nKg > 0 ? 'net_producer' : ($nKg < 0 ? 'net_consumer' : 'neutral'),
                'impact_color'      => $nKg > 0 ? 'success' : ($nKg < 0 ? 'danger' : 'secondary'),
            ]);
        }
        $netImpactSorted = $netImpact->sortByDesc('net_quantity')->values();

        // ─── Category impact ────────────────────────────────────
        $categoryImpact = $netImpact->groupBy('category')->map(function ($items, $category) {
            return (object) [
                'category'          => $category,
                'consumed_quantity' => round($items->sum('consumed_quantity'), 2),
                'produced_quantity' => round($items->sum('produced_quantity'), 2),
                'net_quantity'      => round($items->sum('net_quantity'), 2),
                'consumed_value'    => round($items->sum('consumed_cost'), 2),
                'produced_value'    => round($items->sum('produced_cost'), 2),
                'net_value'         => round($items->sum('net_value'), 2),
                'product_count'     => $items->count(),
            ];
        })->values();

        // ─── Monthly impact (single clean block) ────────────────
        $monthlyBuckets = [];
        foreach ($completedOrders as $order) {
            $mk = $order->completed_at
                ? $order->completed_at->format('Y-m')
                : $order->created_at->format('Y-m');
            $r = $orderRollups[$order->id];
            $monthlyBuckets[$mk] ??= ['orders' => 0, 'inKg' => 0.0, 'outKg' => 0.0, 'inVal' => 0.0, 'rev' => 0.0];
            $monthlyBuckets[$mk]['orders']++;
            $monthlyBuckets[$mk]['inKg']  += $r['input_kg'];
            $monthlyBuckets[$mk]['outKg'] += $r['output_kg'];
            $monthlyBuckets[$mk]['inVal'] += $r['input_cost'];
            $monthlyBuckets[$mk]['rev']   += $r['revenue'];
        }
        ksort($monthlyBuckets);
        $monthlyImpact = [];
        foreach ($monthlyBuckets as $ym => $b) {
            $monthlyImpact[] = [
                'month'           => Carbon::parse($ym . '-01')->format('M Y'),
                'orders'          => $b['orders'],
                'input_quantity'  => round($b['inKg'], 2),
                'output_quantity' => round($b['outKg'], 2),
                'net_quantity'    => round($b['outKg'] - $b['inKg'], 2),
                'input_value'     => round($b['inVal'], 2),
                'output_value'    => round($b['rev'], 2),
                'net_value'       => round($b['rev'] - $b['inVal'], 2),
            ];
        }

        // ─── Paginated impact by order ──────────────────────────
        $impactByOrder = $completedOrders->map(function ($order) use ($orderRollups) {
            $r = $orderRollups[$order->id];
            return (object) [
                'id'                => $order->id,
                'production_number' => $order->production_number,
                'status'            => $order->status_label,
                'status_badge'      => $order->status_badge,
                'location'          => $order->location->name ?? '-',
                'created_at'        => $order->created_at->format('Y-m-d'),
                'input_quantity'    => round($r['input_kg'], 2),
                'output_quantity'   => round($r['output_kg'], 2),
                'net_quantity'      => round($r['net_kg'], 2),
                'input_value'       => round($r['input_cost'], 2),
                'output_value'      => round($r['revenue'], 2),
                'net_value'         => round($r['net_value'], 2),
                'defective'         => round($r['defective'], 2),
                'waste'             => round($r['waste'], 2),
            ];
        })->values();

        $totalRows = $impactByOrder->count();
        $lastPage  = max(1, (int) ceil($totalRows / $perPage));
        $page      = min($page, $lastPage);
        $offset    = ($page - 1) * $perPage;
        $paginated = $impactByOrder->slice($offset, $perPage)->values();

        // ─── Summary ────────────────────────────────────────────
        $impactSummary = [
            'total_orders'            => $orders->count(),
            'completed_orders'        => $completedOrders->count(),
            'total_input_quantity'    => round($totalInKg, 2),
            'total_output_quantity'   => round($totalOutKg, 2),
            'net_inventory_change'    => round($netKg, 2),
            'total_input_value'       => round($totalInVal, 2),
            'total_output_value'      => round($totalRev, 2),
            'net_value_change'        => round($netValue, 2),
            'total_consumed_products' => $consumedSummary->count(),
            'total_produced_products' => $producedSummary->count(),
            'total_defective'         => round($totalDefKg, 2),
            'total_waste'             => round($totalWasteKg, 2),
            'inventory_turnover'      => $totalInKg > 0 ? round($totalOutKg / $totalInKg, 2) : 0,
            'overall_yield'           => $totalInKg > 0 ? round(($totalOutKg / $totalInKg) * 100, 2) : 0,
            'loss_kg'                 => round(max(0, $totalInKg - $totalOutKg), 2),
            'loss_rate'               => $totalInKg > 0
                ? round((max(0, $totalInKg - $totalOutKg) / $totalInKg) * 100, 2) : 0,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'impactSummary'   => $impactSummary,
                'topConsumed'     => $consumedSummary->take(10)->values(),
                'topProduced'     => $producedSummary->take(10)->values(),
                'netImpactSorted' => $netImpactSorted,
                'categoryImpact'  => $categoryImpact,
                'monthlyImpact'   => $monthlyImpact,
                'impactByOrder'   => $paginated,
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
     * Production Quality Analysis Report
     * KG-normalized quality metrics across all production orders
     */
    public function qualityAnalysis(Request $request)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = tenant_is_single_shop($tenantId);

        $startDate     = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate       = $request->get('end_date', now()->format('Y-m-d'));
        $locationId    = $request->get('location_id');
        $variantId     = $request->get('variant_id');
        $status        = $request->get('status', 'completed');
        $qualityStatus = $request->get('quality_status', 'all');
        $perPage       = (int) $request->get('per_page', 15);

        $locations = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);

        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['product:id,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'weight']);

        $statuses = [
            ['value' => 'all',                               'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_COMPLETED,   'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
        ];

        $qualityStatuses = [
            ['value' => 'all',      'label' => __('pagination.all_quality_statuses')],
            ['value' => 'accepted', 'label' => __('pagination.accepted')],
            ['value' => 'rejected', 'label' => __('pagination.rejected')],
            ['value' => 'pending',  'label' => __('pagination.pending')],
        ];

        return view('reports.production.quality-analysis', compact(
            'locations', 'variants', 'statuses', 'qualityStatuses',
            'startDate', 'endDate', 'locationId', 'variantId', 'status', 'qualityStatus', 'perPage', 'isSingleShop'
        ));
    }

    public function qualityAnalysisData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('production reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('production_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate     = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate       = $request->get('end_date', now()->format('Y-m-d'));
        $locationId    = $request->get('location_id');
        $variantId     = $request->get('variant_id');
        $status        = $request->get('status', 'completed');
        $qualityStatus = $request->get('quality_status', 'all');
        $perPage       = (int) $request->get('per_page', 15);
        $page          = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $query = ProductionOrder::with([
                'inputs.productVariant.product.category',
                'outputs.productVariant.product.category',
                'location:id,name',
                'createdBy:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        if ($locationId)                  $query->where('location_id', $locationId);
        if ($status && $status !== 'all') $query->where('status', $status);
        if ($variantId) {
            $query->where(function ($q) use ($variantId) {
                $q->whereHas('inputs',  fn($s) => $s->where('product_variant_id', $variantId))
                ->orWhereHas('outputs', fn($s) => $s->where('product_variant_id', $variantId));
            });
        }

        $filterInputStatuses = [];
        $filterOutputStatuses = [];
        if ($qualityStatus !== 'all') {
            switch ($qualityStatus) {
                case 'accepted':
                    $filterInputStatuses  = [ProductionOrderInput::QUALITY_ACCEPTED];
                    $filterOutputStatuses = [ProductionOrderOutput::QUALITY_APPROVED];
                    break;
                case 'rejected':
                    $filterInputStatuses  = [ProductionOrderInput::QUALITY_REJECTED];
                    $filterOutputStatuses = [ProductionOrderOutput::QUALITY_REJECTED];
                    break;
                case 'pending':
                    $filterInputStatuses  = [ProductionOrderInput::QUALITY_PENDING];
                    $filterOutputStatuses = [ProductionOrderOutput::QUALITY_PENDING];
                    break;
            }
        }

        $orders          = $query->get();
        $completedOrders = $orders->where('status', ProductionOrder::STATUS_COMPLETED);

        // ─── Per-order rollup (weight-aware) ────────────────────
        $orderRollups = [];

        foreach ($orders as $order) {
            $inKg = 0.0; $inKgOk = 0.0; $inKgRej = 0.0; $inKgPend = 0.0; $inWasteKg = 0.0;
            $inCnt = 0;  $inCntOk = 0;  $inCntRej = 0;  $inCntPend = 0;

            foreach ($order->inputs as $input) {
                $v = $input->productVariant;
                if (!$v) continue;
                $qty = (float) $input->actual_quantity;
                $kg  = $v->toKilograms($qty);            // ← weight-aware
                $inWasteKg += $v->toKilograms((float) $input->waste_quantity);

                if (!empty($filterInputStatuses)
                    && !in_array($input->quality_status, $filterInputStatuses, true)) continue;

                $inKg += $kg; $inCnt++;

                switch ($input->quality_status) {
                    case ProductionOrderInput::QUALITY_ACCEPTED:
                        $inKgOk += $kg; $inCntOk++; break;
                    case ProductionOrderInput::QUALITY_REJECTED:
                        $inKgRej += $kg; $inCntRej++; break;
                    default:
                        $inKgPend += $kg; $inCntPend++; break;
                }
            }

            $outKg = 0.0; $outKgOk = 0.0; $outKgRej = 0.0; $outKgPend = 0.0; $outDefKg = 0.0;
            $outCnt = 0; $outCntOk = 0;  $outCntRej = 0;   $outCntPend = 0;

            foreach ($order->outputs as $output) {
                $v = $output->productVariant;
                if (!$v) continue;
                $qty  = (float) $output->actual_quantity;
                $kg   = $v->toKilograms($qty);
                $outDefKg += $v->toKilograms((float) $output->defective_quantity);

                if (!empty($filterOutputStatuses)
                    && !in_array($output->quality_status, $filterOutputStatuses, true)) continue;

                $outKg += $kg; $outCnt++;

                switch ($output->quality_status) {
                    case ProductionOrderOutput::QUALITY_APPROVED:
                        $outKgOk += $kg; $outCntOk++; break;
                    case ProductionOrderOutput::QUALITY_REJECTED:
                        $outKgRej += $kg; $outCntRej++; break;
                    default:
                        $outKgPend += $kg; $outCntPend++; break;
                }
            }

            $orderRollups[$order->id] = [
                'in_kg' => $inKg, 'in_kg_ok' => $inKgOk, 'in_kg_rej' => $inKgRej,
                'in_kg_pend' => $inKgPend, 'in_waste_kg' => $inWasteKg,
                'in_cnt' => $inCnt, 'in_cnt_ok' => $inCntOk, 'in_cnt_rej' => $inCntRej, 'in_cnt_pend' => $inCntPend,
                'out_kg' => $outKg, 'out_kg_ok' => $outKgOk, 'out_kg_rej' => $outKgRej,
                'out_kg_pend' => $outKgPend, 'out_def_kg' => $outDefKg,
                'out_cnt' => $outCnt, 'out_cnt_ok' => $outCntOk, 'out_cnt_rej' => $outCntRej, 'out_cnt_pend' => $outCntPend,
            ];
        }

        // ─── Aggregate ───────────────────────────────────────────
        $agg = array_fill_keys([
            'in_kg','in_kg_ok','in_kg_rej','in_kg_pend','in_waste_kg',
            'in_cnt','in_cnt_ok','in_cnt_rej','in_cnt_pend',
            'out_kg','out_kg_ok','out_kg_rej','out_kg_pend','out_def_kg',
            'out_cnt','out_cnt_ok','out_cnt_rej','out_cnt_pend',
        ], 0);
        $aggFloat = ['in_kg','in_kg_ok','in_kg_rej','in_kg_pend','in_waste_kg',
                    'out_kg','out_kg_ok','out_kg_rej','out_kg_pend','out_def_kg'];
        foreach ($aggFloat as $k) $agg[$k] = 0.0;

        foreach ($orderRollups as $r) {
            $agg['in_kg']       += $r['in_kg'];
            $agg['in_kg_ok']    += $r['in_kg_ok'];
            $agg['in_kg_rej']   += $r['in_kg_rej'];
            $agg['in_kg_pend']  += $r['in_kg_pend'];
            $agg['in_waste_kg'] += $r['in_waste_kg'];
            $agg['in_cnt']      += $r['in_cnt'];
            $agg['in_cnt_ok']   += $r['in_cnt_ok'];
            $agg['in_cnt_rej']  += $r['in_cnt_rej'];
            $agg['in_cnt_pend'] += $r['in_cnt_pend'];

            $agg['out_kg']      += $r['out_kg'];
            $agg['out_kg_ok']   += $r['out_kg_ok'];
            $agg['out_kg_rej']  += $r['out_kg_rej'];
            $agg['out_kg_pend'] += $r['out_kg_pend'];
            $agg['out_def_kg']  += $r['out_def_kg'];
            $agg['out_cnt']     += $r['out_cnt'];
            $agg['out_cnt_ok']  += $r['out_cnt_ok'];
            $agg['out_cnt_rej'] += $r['out_cnt_rej'];
            $agg['out_cnt_pend']+= $r['out_cnt_pend'];
        }

        $inAccRate    = $agg['in_kg']  > 0 ? ($agg['in_kg_ok']  / $agg['in_kg'])  * 100 : 0;
        $outAppRate   = $agg['out_kg'] > 0 ? ($agg['out_kg_ok'] / $agg['out_kg']) * 100 : 0;
        $defRate      = $agg['out_kg'] > 0 ? ($agg['out_def_kg'] / $agg['out_kg']) * 100 : 0;
        $wasteRate    = $agg['in_kg']  > 0 ? ($agg['in_waste_kg'] / $agg['in_kg']) * 100 : 0;

        $totalKg = $agg['in_kg'] + $agg['out_kg'];
        $goodKg  = $agg['in_kg_ok'] + $agg['out_kg_ok'];
        $overallScore = $totalKg > 0 ? ($goodKg / $totalKg) * 100 : 0;

        $qualitySummary = [
            'total_orders'     => $orders->count(),
            'completed_orders' => $completedOrders->count(),
            'total_inputs'           => $agg['in_cnt'],
            'accepted_inputs'        => $agg['in_cnt_ok'],
            'rejected_inputs'        => $agg['in_cnt_rej'],
            'pending_inputs'         => $agg['in_cnt_pend'],
            'input_acceptance_rate'  => round($inAccRate, 2),
            'total_input_kg'         => round($agg['in_kg'], 2),
            'accepted_input_kg'      => round($agg['in_kg_ok'], 2),
            'rejected_input_kg'      => round($agg['in_kg_rej'], 2),
            'pending_input_kg'       => round($agg['in_kg_pend'], 2),
            'total_input_waste_kg'   => round($agg['in_waste_kg'], 2),
            'waste_rate'             => round($wasteRate, 2),
            'total_outputs'          => $agg['out_cnt'],
            'approved_outputs'       => $agg['out_cnt_ok'],
            'rejected_outputs'       => $agg['out_cnt_rej'],
            'pending_outputs'        => $agg['out_cnt_pend'],
            'output_approval_rate'   => round($outAppRate, 2),
            'total_output_kg'        => round($agg['out_kg'], 2),
            'approved_output_kg'     => round($agg['out_kg_ok'], 2),
            'rejected_output_kg'     => round($agg['out_kg_rej'], 2),
            'pending_output_kg'      => round($agg['out_kg_pend'], 2),
            'total_defective_kg'     => round($agg['out_def_kg'], 2),
            'defective_rate'         => round($defRate, 2),
            'overall_quality_score'  => round($overallScore, 2),
            'quality_rating'         => $this->getQualityRating($overallScore),
            'quality_color'          => $this->getQualityColor($overallScore),
        ];

        // ─── Quality by order (paginated) ───────────────────────
        $qualityByOrder = $orders->map(function ($order) use ($orderRollups) {
            $r = $orderRollups[$order->id];
            $inAcc   = $r['in_kg']  > 0 ? ($r['in_kg_ok']  / $r['in_kg'])  * 100 : 0;
            $outApp  = $r['out_kg'] > 0 ? ($r['out_kg_ok'] / $r['out_kg']) * 100 : 0;
            $defRate = $r['out_kg'] > 0 ? ($r['out_def_kg'] / $r['out_kg']) * 100 : 0;
            $wRate   = $r['in_kg']  > 0 ? ($r['in_waste_kg'] / $r['in_kg']) * 100 : 0;
            $tot = $r['in_kg'] + $r['out_kg'];
            $good = $r['in_kg_ok'] + $r['out_kg_ok'];
            $score = $tot > 0 ? ($good / $tot) * 100 : 0;

            return (object) [
                'id'                    => $order->id,
                'production_number'     => $order->production_number,
                'status'                => $order->status_label,
                'status_badge'          => $order->status_badge,
                'location'              => $order->location->name ?? '-',
                'created_at'            => $order->created_at->format('Y-m-d'),
                'input_kg_total'        => round($r['in_kg'], 2),
                'output_kg_total'       => round($r['out_kg'], 2),
                'input_acceptance_rate' => round($inAcc, 2),
                'output_approval_rate'  => round($outApp, 2),
                'defective_rate'        => round($defRate, 2),
                'waste_rate'            => round($wRate, 2),
                'overall_quality_score' => round($score, 2),
                'quality_rating'        => $this->getQualityRating($score),
                'quality_color'         => $this->getQualityColor($score),
            ];
        })->values();

        $totalRows = $qualityByOrder->count();
        $lastPage  = max(1, (int) ceil($totalRows / $perPage));
        $page      = min($page, $lastPage);
        $offset    = ($page - 1) * $perPage;
        $paginated = $qualityByOrder->slice($offset, $perPage)->values();

        // ─── Category quality ───────────────────────────────────
        $catBuckets = [];
        foreach ($orders as $order) {
            foreach ($order->outputs as $output) {
                $v = $output->productVariant;
                if (!$v) continue;
                if (!empty($filterOutputStatuses)
                    && !in_array($output->quality_status, $filterOutputStatuses, true)) continue;

                $qty = (float) $output->actual_quantity;
                $kg  = $v->toKilograms($qty);
                $def = $v->toKilograms((float) $output->defective_quantity);
                $cat = $v->product->category->name ?? 'Uncategorized';

                $catBuckets[$cat] ??= ['total' => 0.0, 'def' => 0.0, 'ok' => 0.0, 'rej' => 0.0];
                $catBuckets[$cat]['total'] += $kg;
                $catBuckets[$cat]['def']   += $def;
                if ($output->quality_status === ProductionOrderOutput::QUALITY_APPROVED) $catBuckets[$cat]['ok']  += $kg;
                if ($output->quality_status === ProductionOrderOutput::QUALITY_REJECTED) $catBuckets[$cat]['rej'] += $kg;
            }
        }
        $categoryQualitySummary = collect($catBuckets)->map(function ($b, $cat) {
            $good = max(0, $b['total'] - $b['def']);
            return (object) [
                'category'       => $cat,
                'total_kg'       => round($b['total'], 2),
                'defective_kg'   => round($b['def'], 2),
                'approved_kg'    => round($b['ok'], 2),
                'rejected_kg'    => round($b['rej'], 2),
                'defective_rate' => $b['total'] > 0 ? round(($b['def'] / $b['total']) * 100, 2) : 0,
                'approval_rate'  => $b['total'] > 0 ? round(($b['ok']  / $b['total']) * 100, 2) : 0,
                'quality_score'  => $b['total'] > 0 ? round(($good / $b['total']) * 100, 2) : 0,
            ];
        })->values();

        // ─── Monthly quality trends ─────────────────────────────
        $monthBuckets = [];
        foreach ($completedOrders as $order) {
            $mk = $order->completed_at
                ? $order->completed_at->format('Y-m')
                : $order->created_at->format('Y-m');
            $r = $orderRollups[$order->id];
            $monthBuckets[$mk] ??= ['orders' => 0, 'inKg' => 0.0, 'inOk' => 0.0, 'wKg' => 0.0,
                                    'outKg' => 0.0, 'outOk' => 0.0, 'defKg' => 0.0];
            $monthBuckets[$mk]['orders']++;
            $monthBuckets[$mk]['inKg']  += $r['in_kg'];
            $monthBuckets[$mk]['inOk']  += $r['in_kg_ok'];
            $monthBuckets[$mk]['wKg']   += $r['in_waste_kg'];
            $monthBuckets[$mk]['outKg'] += $r['out_kg'];
            $monthBuckets[$mk]['outOk'] += $r['out_kg_ok'];
            $monthBuckets[$mk]['defKg'] += $r['out_def_kg'];
        }
        ksort($monthBuckets);
        $monthlyQuality = [];
        foreach ($monthBuckets as $ym => $b) {
            $tot = $b['inKg'] + $b['outKg'];
            $good = $b['inOk'] + $b['outOk'];
            $monthlyQuality[] = [
                'month'            => Carbon::parse($ym . '-01')->format('M Y'),
                'orders'           => $b['orders'],
                'input_acceptance' => $b['inKg']  > 0 ? round(($b['inOk']  / $b['inKg'])  * 100, 2) : 0,
                'output_approval'  => $b['outKg'] > 0 ? round(($b['outOk'] / $b['outKg']) * 100, 2) : 0,
                'defective_rate'   => $b['outKg'] > 0 ? round(($b['defKg'] / $b['outKg']) * 100, 2) : 0,
                'waste_rate'       => $b['inKg']  > 0 ? round(($b['wKg']   / $b['inKg'])  * 100, 2) : 0,
                'overall_score'    => $tot > 0 ? round(($good / $tot) * 100, 2) : 0,
            ];
        }

        // ─── Product quality (top 10) ───────────────────────────
        $varBuckets = [];
        foreach ($orders as $order) {
            foreach ($order->outputs as $output) {
                $v = $output->productVariant;
                if (!$v) continue;
                if (!empty($filterOutputStatuses)
                    && !in_array($output->quality_status, $filterOutputStatuses, true)) continue;

                $qty = (float) $output->actual_quantity;
                $kg  = $v->toKilograms($qty);
                $def = $v->toKilograms((float) $output->defective_quantity);
                $vid = $v->id;
                $varBuckets[$vid] ??= [
                    'variant_id' => $vid, 'variant_name' => $v->name, 'variant_sku' => $v->sku,
                    'category' => $v->product->category->name ?? 'Uncategorized',
                    'weight' => (float) $v->weight,
                    'qty' => 0.0, 'kg' => 0.0, 'def' => 0.0, 'order_ids' => [],
                ];
                $varBuckets[$vid]['qty'] += $qty;
                $varBuckets[$vid]['kg']  += $kg;
                $varBuckets[$vid]['def'] += $def;
                $varBuckets[$vid]['order_ids'][$order->id] = true;
            }
        }
        $productQualitySummary = collect($varBuckets)->map(function ($b) {
            $good = max(0, $b['kg'] - $b['def']);
            return (object) [
                'variant_id'         => $b['variant_id'],
                'variant_name'       => $b['variant_name'],
                'variant_sku'        => $b['variant_sku'],
                'category'           => $b['category'],
                'weight'             => $b['weight'],
                'total_quantity'     => round($b['qty'], 2),
                'total_kg'           => round($b['kg'], 2),
                'defective_kg'       => round($b['def'], 2),
                'defective_rate'     => $b['kg'] > 0 ? round(($b['def'] / $b['kg']) * 100, 2) : 0,
                'approval_rate'      => $b['kg'] > 0 ? round(($good / $b['kg']) * 100, 2) : 0,
                'quality_score'      => $b['kg'] > 0 ? round(($good / $b['kg']) * 100, 2) : 0,
                'order_count'        => count($b['order_ids']),
            ];
        })->sortByDesc('quality_score')->values()->take(10);

        return response()->json([
            'success' => true,
            'data' => [
                'qualitySummary'        => $qualitySummary,
                'monthlyQuality'        => $monthlyQuality,
                'categoryQualitySummary'=> $categoryQualitySummary,
                'productQualitySummary' => $productQualitySummary,
                'qualityByOrder'        => $paginated,
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
     * Quality rating (KG-weighted score, 0–100)
     */
    private function getQualityRating($score)
    {
        if ($score >= 90) return 'Excellent';
        if ($score >= 75) return 'Good';
        if ($score >= 60) return 'Average';
        if ($score >= 40) return 'Below Average';
        return 'Poor';
    }

    /**
     * Quality color (KG-weighted score, 0–100)
     */
    private function getQualityColor($score)
    {
        if ($score >= 90) return 'success';
        if ($score >= 75) return 'info';
        if ($score >= 60) return 'warning';
        if ($score >= 40) return 'danger';
        return 'dark';
    }


    /**
     * Production Input vs Output Report
     * KG-normalized comparison of inputs (raw materials) vs outputs (finished goods)
     */
    public function inputOutput(Request $request)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = tenant_is_single_shop($tenantId);

        $startDate      = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate        = $request->get('end_date', now()->format('Y-m-d'));
        $locationId     = $request->get('location_id');
        $variantId      = $request->get('variant_id');
        $status         = $request->get('status', 'completed');
        $comparisonType = $request->get('comparison_type', 'quantity');
        $perPage        = (int) $request->get('per_page', 15);

        $locations = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)->with(['product:id,name'])
            ->orderBy('name')->get(['id', 'name', 'sku', 'weight']);

        $statuses = [
            ['value' => 'all',                               'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_COMPLETED,   'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
            ['value' => ProductionOrder::STATUS_DRAFT,       'label' => __('pagination.draft')],
        ];

        $comparisonTypes = [
            ['value' => 'quantity', 'label' => __('pagination.quantity_comparison')],
            ['value' => 'cost',     'label' => __('pagination.cost_comparison')],
            ['value' => 'both',     'label' => __('pagination.both_comparison')],
        ];

        return view('reports.production.input-output', compact(
            'locations', 'variants', 'statuses', 'comparisonTypes',
            'startDate', 'endDate', 'locationId', 'variantId', 'status', 'comparisonType', 'perPage', 'isSingleShop'
        ));
    }

    public function inputOutputData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('production reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('production_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate      = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate        = $request->get('end_date', now()->format('Y-m-d'));
        $locationId     = $request->get('location_id');
        $variantId      = $request->get('variant_id');
        $status         = $request->get('status', 'completed');
        $comparisonType = $request->get('comparison_type', 'quantity');
        $perPage        = (int) $request->get('per_page', 15);
        $page           = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $query = ProductionOrder::with([
                'inputs.productVariant.product.category',
                'outputs.productVariant.product.category',
                'location:id,name',
                'createdBy:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        if ($locationId)                  $query->where('location_id', $locationId);
        if ($status && $status !== 'all') $query->where('status', $status);
        if ($variantId) {
            $query->where(function ($q) use ($variantId) {
                $q->whereHas('inputs',  fn($s) => $s->where('product_variant_id', $variantId))
                ->orWhereHas('outputs', fn($s) => $s->where('product_variant_id', $variantId));
            });
        }

        $orders           = $query->get();
        $completedOrders  = $orders->where('status', ProductionOrder::STATUS_COMPLETED);

        // ─── Per-order rollup ────────────────────────────────────
        $orderRollups = [];
        $inputRows  = collect();
        $outputRows = collect();

        foreach ($orders as $order) {
            $inKg = 0.0; $outKg = 0.0;
            $revenue = 0.0;

            foreach ($order->inputs as $input) {
                $v = $input->productVariant;
                if (!$v) continue;
                $qty = (float) $input->actual_quantity;
                $kg  = $v->toKilograms($qty);              // ← weight-aware
                $inKg += $kg;

                $inputRows->push((object) [
                    'order_id'     => $order->id,
                    'variant_id'   => $v->id,
                    'variant_name' => $v->name,
                    'variant_sku'  => $v->sku,
                    'category'     => $v->product->category->name ?? 'Uncategorized',
                    'weight'       => (float) $v->weight,
                    'quantity'     => $qty,
                    'total_kg'     => $kg,
                    'cost'         => (float) $input->actual_cost,
                ]);
            }

            foreach ($order->outputs as $output) {
                $v = $output->productVariant;
                if (!$v) continue;
                $qty  = (float) $output->actual_quantity;
                $kg   = $v->toKilograms($qty);
                $sell = (float) ($v->selling_price ?? 0);

                $outKg   += $kg;
                $revenue += $qty * $sell;

                $outputRows->push((object) [
                    'order_id'      => $order->id,
                    'variant_id'    => $v->id,
                    'variant_name'  => $v->name,
                    'variant_sku'   => $v->sku,
                    'category'      => $v->product->category->name ?? 'Uncategorized',
                    'weight'        => (float) $v->weight,
                    'quantity'      => $qty,
                    'total_kg'      => $kg,
                    'revenue'       => $qty * $sell,
                    'selling_price' => $sell,
                ]);
            }

            $orderRollups[$order->id] = [
                'input_kg'   => $inKg,
                'output_kg'  => $outKg,
                'input_cost' => (float) $order->total_input_cost,
                'revenue'    => $revenue,
                'qty_diff'   => $outKg - $inKg,
                'cost_diff'  => $revenue - (float) $order->total_input_cost,
                'yield'      => $inKg > 0 ? ($outKg / $inKg) * 100 : 0,
                'loss_kg'    => max(0, $inKg - $outKg),
            ];
        }

        // ─── Totals ──────────────────────────────────────────────
        $totalInKg = $totalOutKg = 0.0;
        $totalInCost = $totalRev = 0.0;
        foreach ($orderRollups as $r) {
            $totalInKg   += $r['input_kg'];
            $totalOutKg  += $r['output_kg'];
            $totalInCost += $r['input_cost'];
            $totalRev    += $r['revenue'];
        }
        $netQty  = $totalOutKg - $totalInKg;
        $netCost = $totalRev - $totalInCost;
        $n       = $orders->count();

        $inputOutputSummary = [
            'total_orders'          => $n,
            'completed_orders'      => $completedOrders->count(),
            'total_input_kg'        => round($totalInKg, 2),
            'total_output_kg'       => round($totalOutKg, 2),
            'net_quantity'          => round($netQty, 2),
            'loss_kg'               => round(max(0, $totalInKg - $totalOutKg), 2),
            'quantity_ratio'        => $totalInKg > 0 ? round($totalOutKg / $totalInKg, 3) : 0,
            'quantity_efficiency'   => $totalInKg > 0 ? round(($totalOutKg / $totalInKg) * 100, 2) : 0,
            'total_input_cost'      => round($totalInCost, 2),
            'total_output_cost'     => round($totalRev, 2),
            'total_revenue'         => round($totalRev, 2),
            'net_cost'              => round($netCost, 2),
            'net_value'             => round($netCost, 2),
            'cost_ratio'            => $totalInCost > 0 ? round($totalRev / $totalInCost, 3) : 0,
            'cost_efficiency'       => $totalInCost > 0 ? round(($totalRev / $totalInCost) * 100, 2) : 0,
            'avg_input_qty_per_order'  => $n > 0 ? round($totalInKg / $n, 2) : 0,
            'avg_output_qty_per_order' => $n > 0 ? round($totalOutKg / $n, 2) : 0,
            'avg_input_cost_per_order' => $n > 0 ? round($totalInCost / $n, 2) : 0,
            'avg_output_cost_per_order'=> $n > 0 ? round($totalRev / $n, 2) : 0,
            'overall_yield'         => $totalInKg > 0 ? round(($totalOutKg / $totalInKg) * 100, 2) : 0,
            'loss_rate'             => $totalInKg > 0
                ? round((max(0, $totalInKg - $totalOutKg) / $totalInKg) * 100, 2) : 0,
            'cost_per_kg_input'     => $totalInKg  > 0 ? round($totalInCost / $totalInKg, 2)  : 0,
            'cost_per_kg_output'    => $totalOutKg > 0 ? round($totalInCost / $totalOutKg, 2) : 0,
            'revenue_per_kg'        => $totalOutKg > 0 ? round($totalRev / $totalOutKg, 2)    : 0,
            'profit_per_kg'         => $totalOutKg > 0 ? round($netCost / $totalOutKg, 2)     : 0,
        ];

        // ─── Per-order comparison + pagination ──────────────────
        $comparisonByOrder = $orders->map(function ($order) use ($orderRollups) {
            $r = $orderRollups[$order->id];
            return (object) [
                'id'                => $order->id,
                'production_number' => $order->production_number,
                'status'            => $order->status_label,
                'status_badge'      => $order->status_badge,
                'location'          => $order->location->name ?? '-',
                'created_at'        => $order->created_at->format('Y-m-d'),
                'input_quantity'    => round($r['input_kg'], 2),
                'output_quantity'   => round($r['output_kg'], 2),
                'qty_difference'    => round($r['qty_diff'], 2),
                'qty_efficiency'    => round($r['yield'], 2),
                'input_cost'        => round($r['input_cost'], 2),
                'output_cost'       => round($r['revenue'], 2),
                'cost_difference'   => round($r['cost_diff'], 2),
                'cost_efficiency'   => $r['input_cost'] > 0
                    ? round(($r['revenue'] / $r['input_cost']) * 100, 2) : 0,
                'loss_kg'           => round($r['loss_kg'], 2),
            ];
        })->values();

        $totalRows = $comparisonByOrder->count();
        $lastPage  = max(1, (int) ceil($totalRows / $perPage));
        $page      = min($page, $lastPage);
        $offset    = ($page - 1) * $perPage;
        $paginated = $comparisonByOrder->slice($offset, $perPage)->values();

        // ─── Category comparison ────────────────────────────────
        $categories = $inputRows->pluck('category')
            ->merge($outputRows->pluck('category'))
            ->unique()->filter();

        $categoryComparison = $categories->map(function ($cat) use ($inputRows, $outputRows) {
            $in  = $inputRows->where('category', $cat);
            $out = $outputRows->where('category', $cat);
            $inKg   = (float) $in->sum('total_kg');
            $outKg  = (float) $out->sum('total_kg');
            $inCost = (float) $in->sum('cost');
            $outRev = (float) $out->sum('revenue');
            return (object) [
                'category'        => $cat,
                'input_kg'        => round($inKg, 2),
                'output_kg'       => round($outKg, 2),
                'qty_difference'  => round($outKg - $inKg, 2),
                'input_cost'      => round($inCost, 2),
                'output_cost'     => round($outRev, 2),
                'output_revenue'  => round($outRev, 2),
                'cost_difference' => round($outRev - $inCost, 2),
            ];
        })->sortByDesc('input_kg')->values();

        // ─── Monthly ────────────────────────────────────────────
        $monthBuckets = [];
        foreach ($completedOrders as $order) {
            $mk = $order->completed_at ? $order->completed_at->format('Y-m') : $order->created_at->format('Y-m');
            $r = $orderRollups[$order->id];
            $monthBuckets[$mk] ??= ['orders' => 0, 'inKg' => 0.0, 'outKg' => 0.0, 'inCost' => 0.0, 'rev' => 0.0];
            $monthBuckets[$mk]['orders']++;
            $monthBuckets[$mk]['inKg']  += $r['input_kg'];
            $monthBuckets[$mk]['outKg'] += $r['output_kg'];
            $monthBuckets[$mk]['inCost']+= $r['input_cost'];
            $monthBuckets[$mk]['rev']   += $r['revenue'];
        }
        ksort($monthBuckets);
        $monthlyComparison = [];
        foreach ($monthBuckets as $ym => $b) {
            $monthlyComparison[] = [
                'month'           => Carbon::parse($ym . '-01')->format('M Y'),
                'orders'          => $b['orders'],
                'input_quantity'  => round($b['inKg'], 2),
                'output_quantity' => round($b['outKg'], 2),
                'yield'           => $b['inKg'] > 0 ? round(($b['outKg'] / $b['inKg']) * 100, 2) : 0,
                'input_cost'      => round($b['inCost'], 2),
                'output_cost'     => round($b['rev'], 2),
            ];
        }

        // ─── Product summary (top 10 by net difference) ─────────
        $allVids = $inputRows->pluck('variant_id')
            ->merge($outputRows->pluck('variant_id'))->unique()->filter();

        $productSummary = $allVids->map(function ($vid) use ($inputRows, $outputRows) {
            $in  = $inputRows->where('variant_id', $vid);
            $out = $outputRows->where('variant_id', $vid);
            $inKg   = (float) $in->sum('total_kg');
            $outKg  = (float) $out->sum('total_kg');
            $inCost = (float) $in->sum('cost');
            $outRev = (float) $out->sum('revenue');
            $src = $in->first() ?: $out->first();
            if (!$src) return null;
            $diff = $outKg - $inKg;
            return (object) [
                'variant_id'      => $vid,
                'variant_name'    => $src->variant_name,
                'variant_sku'     => $src->variant_sku,
                'category'        => $src->category,
                'weight'          => $src->weight,
                'input_kg'        => round($inKg, 2),
                'output_kg'       => round($outKg, 2),
                'qty_difference'  => round($diff, 2),
                'input_cost'      => round($inCost, 2),
                'output_cost'     => round($outRev, 2),
                'cost_difference' => round($outRev - $inCost, 2),
                'type'            => $diff > 0 ? 'net_producer' : ($diff < 0 ? 'net_consumer' : 'neutral'),
            ];
        })->filter()->sortByDesc('qty_difference')->values()->take(10);

        return response()->json([
            'success' => true,
            'data' => [
                'inputOutputSummary' => $inputOutputSummary,
                'monthlyComparison'  => $monthlyComparison,
                'categoryComparison' => $categoryComparison,
                'productSummary'     => $productSummary,
                'comparisonByOrder'  => $paginated,
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
     * Production Waste Report
     * KG-normalized waste + defective analysis across production orders
     */
    public function waste(Request $request)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = tenant_is_single_shop($tenantId);

        $startDate  = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate    = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId  = $request->get('variant_id');
        $status     = $request->get('status', 'completed');
        $wasteType  = $request->get('waste_type', 'all');
        $perPage    = (int) $request->get('per_page', 15);

        $locations = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)->with(['product:id,name'])
            ->orderBy('name')->get(['id', 'name', 'sku', 'weight']);

        $statuses = [
            ['value' => 'all',                               'label' => __('pagination.all_statuses')],
            ['value' => ProductionOrder::STATUS_COMPLETED,   'label' => __('pagination.completed')],
            ['value' => ProductionOrder::STATUS_IN_PROGRESS, 'label' => __('pagination.in_progress')],
        ];

        $wasteTypes = [
            ['value' => 'all',              'label' => __('pagination.all_waste')],
            ['value' => 'input_waste',      'label' => __('pagination.input_waste')],
            ['value' => 'output_defective', 'label' => __('pagination.output_defective')],
        ];

        return view('reports.production.waste', compact(
            'locations', 'variants', 'statuses', 'wasteTypes',
            'startDate', 'endDate', 'locationId', 'variantId', 'status', 'wasteType', 'perPage', 'isSingleShop'
        ));
    }

    public function wasteData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('production reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('production_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate  = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate    = $request->get('end_date', now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId  = $request->get('variant_id');
        $status     = $request->get('status', 'completed');
        $wasteType  = $request->get('waste_type', 'all');
        $perPage    = (int) $request->get('per_page', 15);
        $page       = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $query = ProductionOrder::with([
                'inputs.productVariant.product.category',
                'outputs.productVariant.product.category',
                'location:id,name',
                'createdBy:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        if ($locationId)                  $query->where('location_id', $locationId);
        if ($status && $status !== 'all') $query->where('status', $status);
        if ($variantId) {
            $query->where(function ($q) use ($variantId) {
                $q->whereHas('inputs',  fn($s) => $s->where('product_variant_id', $variantId))
                ->orWhereHas('outputs', fn($s) => $s->where('product_variant_id', $variantId));
            });
        }

        $considerInputs  = in_array($wasteType, ['all', 'input_waste'], true);
        $considerOutputs = in_array($wasteType, ['all', 'output_defective'], true);

        $orders           = $query->get();
        $completedOrders  = $orders->where('status', ProductionOrder::STATUS_COMPLETED);

        // ─── Per-order rollup ────────────────────────────────────
        $orderRollups = [];
        $inputWasteRows  = collect();
        $outputWasteRows = collect();

        foreach ($orders as $order) {
            $inKg = 0.0; $inWasteKg = 0.0; $inWasteCost = 0.0;
            $inCost = (float) $order->total_input_cost;

            if ($considerInputs) {
                foreach ($order->inputs as $input) {
                    $v = $input->productVariant;
                    if (!$v) continue;

                    $qty  = (float) $input->actual_quantity;
                    $kg   = $v->toKilograms($qty);              // ← weight-aware
                    $wKg  = $v->toKilograms((float) $input->waste_quantity);

                    // Per-kg cost so waste cost is correct for bagged products
                    $costPerKg = ($v->weight > 0)
                        ? ((float) ($v->grand_total_cost_price ?? 0)) / max((float) $v->weight, 0.0001)
                        : (float) ($v->grand_total_cost_price ?? 0);

                    $rowWasteCost = $wKg * $costPerKg;

                    $inKg        += $kg;
                    $inWasteKg   += $wKg;
                    $inWasteCost += $rowWasteCost;

                    if ($wKg > 0) {
                        $inputWasteRows->push((object) [
                            'order_id'     => $order->id,
                            'variant_id'   => $v->id,
                            'variant_name' => $v->name,
                            'variant_sku'  => $v->sku,
                            'category'     => $v->product->category->name ?? 'Uncategorized',
                            'weight'       => (float) $v->weight,
                            'total_kg'     => $kg,
                            'waste_kg'     => $wKg,
                            'waste_cost'   => $rowWasteCost,
                        ]);
                    }
                }
            }

            $outKg = 0.0; $defKg = 0.0; $defCost = 0.0; $revenue = 0.0;
            if ($considerOutputs) {
                foreach ($order->outputs as $output) {
                    $v = $output->productVariant;
                    if (!$v) continue;

                    $qty  = (float) $output->actual_quantity;
                    $kg   = $v->toKilograms($qty);
                    $dKg  = $v->toKilograms((float) $output->defective_quantity);
                    $sell = (float) ($v->selling_price ?? 0);

                    $costPerKg = ($v->weight > 0)
                        ? ((float) ($v->grand_total_cost_price ?? 0)) / max((float) $v->weight, 0.0001)
                        : (float) ($v->grand_total_cost_price ?? 0);

                    $rowDefCost = $dKg * $costPerKg;

                    $outKg    += $kg;
                    $defKg    += $dKg;
                    $defCost  += $rowDefCost;
                    $revenue  += $qty * $sell;

                    if ($dKg > 0) {
                        $outputWasteRows->push((object) [
                            'order_id'       => $order->id,
                            'variant_id'     => $v->id,
                            'variant_name'   => $v->name,
                            'variant_sku'    => $v->sku,
                            'category'       => $v->product->category->name ?? 'Uncategorized',
                            'weight'         => (float) $v->weight,
                            'total_kg'       => $kg,
                            'defective_kg'   => $dKg,
                            'defective_cost' => $rowDefCost,
                            'revenue'        => $qty * $sell,
                        ]);
                    }
                }
            }

            $orderRollups[$order->id] = [
                'input_kg'         => $inKg,
                'input_waste_kg'   => $inWasteKg,
                'input_waste_cost' => $inWasteCost,
                'input_cost'       => $inCost,
                'output_kg'        => $outKg,
                'defective_kg'     => $defKg,
                'defective_cost'   => $defCost,
                'revenue'          => $revenue,
                'total_waste_kg'   => $inWasteKg + $defKg,
                'total_waste_cost' => $inWasteCost + $defCost,
            ];
        }

        // ─── Totals ──────────────────────────────────────────────
        $tInKg = $tInWasteKg = $tInWasteCost = 0.0;
        $tOutKg = $tDefKg = $tDefCost = 0.0;
        $tBatch = $tRev = 0.0;

        foreach ($orderRollups as $r) {
            $tInKg        += $r['input_kg'];
            $tInWasteKg   += $r['input_waste_kg'];
            $tInWasteCost += $r['input_waste_cost'];
            $tOutKg       += $r['output_kg'];
            $tDefKg       += $r['defective_kg'];
            $tDefCost     += $r['defective_cost'];
            $tBatch       += $r['input_cost'];
            $tRev         += $r['revenue'];
        }

        $inputWasteRate = $tInKg  > 0 ? ($tInWasteKg / $tInKg)  * 100 : 0;
        $defectiveRate  = $tOutKg > 0 ? ($tDefKg     / $tOutKg) * 100 : 0;
        $totalWasteKg   = $tInWasteKg + $tDefKg;
        $totalWasteCost = $tInWasteCost + $tDefCost;
        $totalBaseKg    = $tInKg + $tOutKg;
        $totalWasteRate = $totalBaseKg > 0 ? ($totalWasteKg / $totalBaseKg) * 100 : 0;
        $wasteCostPct   = $tBatch > 0 ? ($totalWasteCost / $tBatch) * 100 : 0;

        $wasteSummary = [
            'total_orders'          => $orders->count(),
            'completed_orders'      => $completedOrders->count(),
            'total_input_waste_kg'  => round($tInWasteKg, 2),
            'total_input_kg'        => round($tInKg, 2),
            'input_waste_rate'      => round($inputWasteRate, 2),
            'input_waste_cost'      => round($tInWasteCost, 2),
            'total_defective_kg'    => round($tDefKg, 2),
            'total_output_kg'       => round($tOutKg, 2),
            'output_defective_rate' => round($defectiveRate, 2),
            'defective_cost'        => round($tDefCost, 2),
            'total_waste_kg'        => round($totalWasteKg, 2),
            'total_waste_rate'      => round($totalWasteRate, 2),
            'waste_cost'            => round($totalWasteCost, 2),
            'waste_cost_percentage' => round($wasteCostPct, 2),
            'total_cost'            => round($tBatch, 2),
            'good_output'           => round(max(0, $tOutKg - $tDefKg), 2),
            'good_rate'             => $tOutKg > 0
                ? round((max(0, $tOutKg - $tDefKg) / $tOutKg) * 100, 2) : 0,
        ];

        // ─── Waste by order (paginated) ─────────────────────────
        $wasteByOrder = $orders->map(function ($order) use ($orderRollups) {
            $r = $orderRollups[$order->id];
            $inRate = $r['input_kg'] > 0 ? ($r['input_waste_kg'] / $r['input_kg']) * 100 : 0;
            $defRate = $r['output_kg'] > 0 ? ($r['defective_kg'] / $r['output_kg']) * 100 : 0;
            $base = $r['input_kg'] + $r['output_kg'];
            $totRate = $base > 0 ? ($r['total_waste_kg'] / $base) * 100 : 0;
            return (object) [
                'id'                => $order->id,
                'production_number' => $order->production_number,
                'status'            => $order->status_label,
                'status_badge'      => $order->status_badge,
                'location'          => $order->location->name ?? '-',
                'created_at'        => $order->created_at->format('Y-m-d'),
                'input_waste'       => round($r['input_waste_kg'], 2),
                'input_quantity'    => round($r['input_kg'], 2),
                'input_waste_rate'  => round($inRate, 2),
                'defective'         => round($r['defective_kg'], 2),
                'output_quantity'   => round($r['output_kg'], 2),
                'defective_rate'    => round($defRate, 2),
                'total_waste'       => round($r['total_waste_kg'], 2),
                'total_waste_rate'  => round($totRate, 2),
                'waste_cost'        => round($r['total_waste_cost'], 2),
            ];
        })->values();

        $totalRows = $wasteByOrder->count();
        $lastPage  = max(1, (int) ceil($totalRows / $perPage));
        $page      = min($page, $lastPage);
        $offset    = ($page - 1) * $perPage;
        $paginated = $wasteByOrder->slice($offset, $perPage)->values();

        // ─── Category summary ───────────────────────────────────
        $catBuckets = [];
        foreach ($inputWasteRows as $r) {
            $catBuckets[$r->category] ??= ['inWaste' => 0.0, 'inTot' => 0.0, 'def' => 0.0, 'outTot' => 0.0,
                                        'inCost' => 0.0, 'defCost' => 0.0];
            $catBuckets[$r->category]['inWaste'] += $r->waste_kg;
            $catBuckets[$r->category]['inTot']   += $r->total_kg;
            $catBuckets[$r->category]['inCost']  += $r->waste_cost;
        }
        foreach ($outputWasteRows as $r) {
            $catBuckets[$r->category] ??= ['inWaste' => 0.0, 'inTot' => 0.0, 'def' => 0.0, 'outTot' => 0.0,
                                        'inCost' => 0.0, 'defCost' => 0.0];
            $catBuckets[$r->category]['def']     += $r->defective_kg;
            $catBuckets[$r->category]['outTot']  += $r->total_kg;
            $catBuckets[$r->category]['defCost'] += $r->defective_cost;
        }
        $categoryWasteSummary = collect($catBuckets)->map(function ($b, $cat) {
            $base = $b['inTot'] + $b['outTot'];
            $totW = $b['inWaste'] + $b['def'];
            return (object) [
                'category'         => $cat,
                'input_waste'      => round($b['inWaste'], 2),
                'input_total'      => round($b['inTot'], 2),
                'input_waste_rate' => $b['inTot']  > 0 ? round(($b['inWaste'] / $b['inTot']) * 100, 2) : 0,
                'defective'        => round($b['def'], 2),
                'output_total'     => round($b['outTot'], 2),
                'defective_rate'   => $b['outTot'] > 0 ? round(($b['def']     / $b['outTot']) * 100, 2) : 0,
                'total_waste'      => round($totW, 2),
                'total_rate'       => $base > 0 ? round(($totW / $base) * 100, 2) : 0,
                'waste_cost'       => round($b['inCost'] + $b['defCost'], 2),
            ];
        })->sortByDesc('total_waste')->values();

        // ─── Monthly ────────────────────────────────────────────
        $monthBuckets = [];
        foreach ($completedOrders as $order) {
            $mk = $order->completed_at ? $order->completed_at->format('Y-m') : $order->created_at->format('Y-m');
            $r = $orderRollups[$order->id];
            $monthBuckets[$mk] ??= ['orders' => 0, 'inWaste' => 0.0, 'inTot' => 0.0,
                                    'def' => 0.0, 'outTot' => 0.0, 'cost' => 0.0];
            $monthBuckets[$mk]['orders']++;
            $monthBuckets[$mk]['inWaste'] += $r['input_waste_kg'];
            $monthBuckets[$mk]['inTot']   += $r['input_kg'];
            $monthBuckets[$mk]['def']     += $r['defective_kg'];
            $monthBuckets[$mk]['outTot']  += $r['output_kg'];
            $monthBuckets[$mk]['cost']    += $r['total_waste_cost'];
        }
        ksort($monthBuckets);
        $monthlyWaste = [];
        foreach ($monthBuckets as $ym => $b) {
            $base = $b['inTot'] + $b['outTot'];
            $tot  = $b['inWaste'] + $b['def'];
            $monthlyWaste[] = [
                'month'            => Carbon::parse($ym . '-01')->format('M Y'),
                'orders'           => $b['orders'],
                'input_waste'      => round($b['inWaste'], 2),
                'input_waste_rate' => $b['inTot']  > 0 ? round(($b['inWaste'] / $b['inTot']) * 100, 2) : 0,
                'defective'        => round($b['def'], 2),
                'defective_rate'   => $b['outTot'] > 0 ? round(($b['def'] / $b['outTot']) * 100, 2) : 0,
                'total_waste'      => round($tot, 2),
                'total_rate'       => $base > 0 ? round(($tot / $base) * 100, 2) : 0,
                'waste_cost'       => round($b['cost'], 2),
            ];
        }

        // ─── Product summary (top 10) ───────────────────────────
        $varBuckets = [];
        foreach ($inputWasteRows as $r) {
            $varBuckets[$r->variant_id] ??= ['variant_id' => $r->variant_id, 'variant_name' => $r->variant_name,
                                            'variant_sku' => $r->variant_sku, 'category' => $r->category,
                                            'weight' => $r->weight, 'inWaste' => 0.0, 'inTot' => 0.0,
                                            'outWaste' => 0.0, 'outTot' => 0.0, 'cost' => 0.0, 'order_count' => 0];
            $varBuckets[$r->variant_id]['inWaste'] += $r->waste_kg;
            $varBuckets[$r->variant_id]['inTot']   += $r->total_kg;
            $varBuckets[$r->variant_id]['cost']    += $r->waste_cost;
        }
        foreach ($outputWasteRows as $r) {
            $varBuckets[$r->variant_id] ??= ['variant_id' => $r->variant_id, 'variant_name' => $r->variant_name,
                                            'variant_sku' => $r->variant_sku, 'category' => $r->category,
                                            'weight' => $r->weight, 'inWaste' => 0.0, 'inTot' => 0.0,
                                            'outWaste' => 0.0, 'outTot' => 0.0, 'cost' => 0.0, 'order_count' => 0];
            $varBuckets[$r->variant_id]['outWaste'] += $r->defective_kg;
            $varBuckets[$r->variant_id]['outTot']   += $r->total_kg;
            $varBuckets[$r->variant_id]['cost']     += $r->defective_cost;
        }
        $productWasteSummary = collect($varBuckets)->map(function ($b) {
            $base = $b['inTot'] + $b['outTot'];
            $tot  = $b['inWaste'] + $b['outWaste'];
            return (object) [
                'variant_id'        => $b['variant_id'],
                'variant_name'      => $b['variant_name'],
                'variant_sku'       => $b['variant_sku'],
                'category'          => $b['category'],
                'weight'            => $b['weight'],
                'input_waste'       => round($b['inWaste'], 2),
                'output_waste'      => round($b['outWaste'], 2),
                'total_waste'       => round($tot, 2),
                'total_rate'        => $base > 0 ? round(($tot / $base) * 100, 2) : 0,
                'waste_cost'        => round($b['cost'], 2),
            ];
        })->sortByDesc('total_waste')->values()->take(10);

        // ─── Severity distribution ──────────────────────────────
        $severity = ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0];
        foreach ($wasteByOrder as $o) {
            $r = (float) $o->total_waste_rate;
            if ($r <= 5) $severity['low']++;
            elseif ($r <= 15) $severity['medium']++;
            elseif ($r <= 30) $severity['high']++;
            else $severity['critical']++;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'wasteSummary'         => $wasteSummary,
                'monthlyWaste'         => $monthlyWaste,
                'categoryWasteSummary' => $categoryWasteSummary,
                'productWasteSummary'  => $productWasteSummary,
                'severityDistribution' => $severity,
                'wasteByOrder'         => $paginated,
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
     * Production Batch Tracking Report
     * Tracks batches produced and consumed, KG-normalized for cross-variant aggregation
     */
    public function batchTracking(Request $request)
    {
        $tenantId     = $this->getTenantId();
        $isSingleShop = tenant_is_single_shop($tenantId);

        $startDate  = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate    = $request->get('end_date',   now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId  = $request->get('variant_id');
        $batchType  = $request->get('batch_type', 'all');
        $search     = trim((string) $request->get('search', ''));
        $perPage    = (int) $request->get('per_page', 15);

        $locations = Location::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $variants = ProductVariant::where('tenant_id', $tenantId)
            ->where('is_active', true)->with(['product:id,name'])
            ->orderBy('name')->get(['id', 'name', 'sku']);

        $batchTypes = [
            ['value' => 'all',         'label' => __('pagination.all_batches')],
            ['value' => 'produced',    'label' => __('pagination.produced_batches')],
            ['value' => 'consumed',    'label' => __('pagination.consumed_batches')],
            ['value' => 'received',    'label' => __('pagination.received_batches')],
            ['value' => 'transferred', 'label' => __('pagination.transferred_batches')],
            ['value' => 'adjusted',    'label' => __('pagination.adjusted_batches')],
        ];

        return view('reports.production.batch-tracking', compact(
            'locations', 'variants', 'batchTypes',
            'startDate', 'endDate', 'locationId', 'variantId', 'batchType', 'search', 'perPage', 'isSingleShop'
        ));
    }

    public function batchTrackingData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('production reports')) {
            return response()->json(['success' => false, 'message' => __('payments.not_authorized')], 403);
        }
        if (!tenant_can('production_reports')) {
            return response()->json(['success' => false, 'message' => __('payments.feature_not_available_in_plan')], 403);
        }
        $tenantId = $user->tenant_id;

        $startDate  = $request->get('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate    = $request->get('end_date',   now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $variantId  = $request->get('variant_id');
        $batchType  = $request->get('batch_type', 'all');
        $search     = trim((string) $request->get('search', ''));
        $perPage    = (int) $request->get('per_page', 15);
        $page       = max(1, (int) $request->get('page', 1));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        // ─── Base order IDs ─────────────────────────────────────
        $orderQuery = ProductionOrder::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        if ($locationId) $orderQuery->where('location_id', $locationId);
        if ($variantId) {
            $orderQuery->where(function ($q) use ($variantId) {
                $q->whereHas('inputs',  fn($s) => $s->where('product_variant_id', $variantId))
                ->orWhereHas('outputs', fn($s) => $s->where('product_variant_id', $variantId));
            });
        }

        $orderIds = $orderQuery->pluck('id');

        // ─── Batch logs ─────────────────────────────────────────
        $batchLogsQuery = BatchLog::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('production_order_id', $orderIds)
            ->with(['variant:id,name,sku,weight', 'performedBy:id,name'])
            ->orderByDesc('event_date')
            ->orderByDesc('id');

        if ($batchType === 'produced') {
            $batchLogsQuery->where('type', BatchLog::TYPE_PRODUCED);
        } elseif ($batchType === 'consumed') {
            $batchLogsQuery->whereIn('type', [BatchLog::TYPE_DEPLETED, 'consumed']);
        } elseif ($batchType === 'received') {
            $batchLogsQuery->where('type', BatchLog::TYPE_RECEIVED);
        } elseif ($batchType === 'transferred') {
            $batchLogsQuery->whereIn('type', [
                BatchLog::TYPE_TRANSFERRED,
                BatchLog::TYPE_ASSIGNED,
                BatchLog::TYPE_UNASSIGNED,
            ]);
        } elseif ($batchType === 'adjusted') {
            $batchLogsQuery->where('type', BatchLog::TYPE_ADJUSTED);
        }

        if ($search !== '') {
            $batchLogsQuery->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                ->orWhere('variant_name', 'like', "%{$search}%")
                ->orWhere('variant_sku',  'like', "%{$search}%")
                ->orWhere('purchase_order_number', 'like', "%{$search}%")
                ->orWhere('supplier_name', 'like', "%{$search}%");
            });
        }

        $allBatchLogs = $batchLogsQuery->get();

        // ─── Weight lookup (once) ───────────────────────────────
        $variantIds = $allBatchLogs->pluck('variant_id')->filter()->unique()->values();
        $variantWeights = $variantIds->isNotEmpty()
            ? ProductVariant::whereIn('id', $variantIds)->pluck('weight', 'id')->toArray()
            : [];

        $toKg = function ($qty, $variantId) use ($variantWeights) {
            $w = (float) ($variantWeights[$variantId] ?? 0);
            $q = (float) $qty;
            return $w > 0 ? $q * $w : $q;
        };

        // ─── Summary ────────────────────────────────────────────
        $producedLogs = $allBatchLogs->where('type', BatchLog::TYPE_PRODUCED);
        $consumedLogs = $allBatchLogs->whereIn('type', [BatchLog::TYPE_DEPLETED, 'consumed']);

        $totalProducedQty = $totalProducedKg = 0.0;
        $totalConsumedQty = $totalConsumedKg = 0.0;

        foreach ($producedLogs as $log) {
            $q = (float) $log->quantity_change;
            $totalProducedQty += $q;
            $totalProducedKg  += $toKg($q, $log->variant_id);
        }
        foreach ($consumedLogs as $log) {
            $q = abs((float) $log->quantity_change);
            $totalConsumedQty += $q;
            $totalConsumedKg  += $toKg($q, $log->variant_id);
        }

        $totalProducedCost = (float) $producedLogs->sum('total_cost');
        $totalConsumedCost = (float) $consumedLogs->sum('total_cost');

        // Avg unit cost per kg (weighted by kg)
        $avgUnitCostPerKg = 0.0;
        $allWithCost = $allBatchLogs->filter(fn($l) => (float) $l->unit_cost > 0 && (float) $l->quantity_change !== 0);
        if ($allWithCost->count() > 0) {
            $weighted = 0.0;
            $totalKg  = 0.0;
            foreach ($allWithCost as $log) {
                $kg = abs($toKg($log->quantity_change, $log->variant_id));
                if ($kg > 0) {
                    $weighted += (float) $log->unit_cost * $kg;
                    $totalKg  += $kg;
                }
            }
            $avgUnitCostPerKg = $totalKg > 0 ? $weighted / $totalKg : 0;
        }

        $batchSummary = [
            'total_batches'           => $allBatchLogs->count(),
            'produced_batches'        => $producedLogs->count(),
            'consumed_batches'        => $consumedLogs->count(),
            'received_batches'        => $allBatchLogs->where('type', BatchLog::TYPE_RECEIVED)->count(),
            'transferred_batches'     => $allBatchLogs->whereIn('type', [
                BatchLog::TYPE_TRANSFERRED, BatchLog::TYPE_ASSIGNED, BatchLog::TYPE_UNASSIGNED,
            ])->count(),
            'adjusted_batches'        => $allBatchLogs->where('type', BatchLog::TYPE_ADJUSTED)->count(),
            'total_produced_quantity' => round($totalProducedQty, 2),
            'total_consumed_quantity' => round($totalConsumedQty, 2),
            'net_batch_quantity'      => round($totalProducedQty - $totalConsumedQty, 2),
            'total_produced_kg'       => round($totalProducedKg, 2),
            'total_consumed_kg'       => round($totalConsumedKg, 2),
            'net_batch_kg'            => round($totalProducedKg - $totalConsumedKg, 2),
            'total_produced_cost'     => round($totalProducedCost, 2),
            'total_consumed_cost'     => round($totalConsumedCost, 2),
            'net_batch_cost'          => round($totalProducedCost - $totalConsumedCost, 2),
            'unique_batch_numbers'    => $allBatchLogs->pluck('batch_number')->filter()->unique()->count(),
            'unique_variants'         => $allBatchLogs->pluck('variant_id')->filter()->unique()->count(),
            'unique_suppliers'        => $allBatchLogs->pluck('supplier_id')->filter()->unique()->count(),
            'unique_purchase_orders'  => $allBatchLogs->pluck('purchase_order_id')->filter()->unique()->count(),
            'avg_unit_cost_per_kg'    => round($avgUnitCostPerKg, 2),
        ];

        // ─── Enrich rows ────────────────────────────────────────
        $enrichedLogs = $allBatchLogs->map(function ($log) use ($toKg) {
            $change    = (float) $log->quantity_change;
            $direction = $change > 0 ? 'in' : ($change < 0 ? 'out' : 'neutral');

            $sourceType  = 'unknown';
            $sourceLabel = null;
            $sourceSub   = null;

            if ($log->purchase_order_id) {
                $sourceType  = 'purchase_order';
                $sourceLabel = $log->purchase_order_number ?: "#{$log->purchase_order_id}";
                $sourceSub   = $log->supplier_name;
            } elseif ($log->production_order_id) {
                $sourceType  = 'production_order';
                $sourceLabel = "PRD-#{$log->production_order_id}";
                $sourceSub   = $log->metadata['production_number'] ?? null;
            }

            $unitCost  = (float) ($log->unit_cost ?? 0);
            $totalCost = (float) ($log->total_cost ?? 0);
            $weight    = (float) ($log->variant?->weight ?? 0);

            return (object) [
                'id'                 => $log->id,
                'batch_id'           => $log->batch_id,
                'batch_number'       => $log->batch_number,
                'type'               => $log->type,
                'type_label'         => $log->type_label,
                'type_color'         => $log->type_color,
                'type_icon'          => $log->type_icon,
                'direction'          => $direction,
                'variant_id'         => $log->variant_id,
                'variant_name'       => $log->variant_name ?: $log->variant?->name,
                'variant_sku'        => $log->variant_sku  ?: $log->variant?->sku,
                'weight'             => $weight,
                'quantity_change'    => $change,
                'quantity_before'    => (float) $log->quantity_before,
                'quantity_after'     => (float) $log->quantity_after,
                'quantity_change_kg' => $toKg($change, $log->variant_id),
                'quantity_before_kg' => $toKg((float) $log->quantity_before, $log->variant_id),
                'quantity_after_kg'  => $toKg((float) $log->quantity_after,  $log->variant_id),
                'unit_cost'          => $unitCost,
                'unit_cost_per_kg'   => $weight > 0 ? $unitCost / $weight : $unitCost,
                'total_cost'         => $totalCost,
                'expiry_date'        => $log->expiry_date,
                'event_date'         => $log->event_date?->format('Y-m-d H:i:s'),
                'performed_by'       => $log->performedBy?->name,
                'source_type'        => $sourceType,
                'source_label'       => $sourceLabel,
                'source_sub'         => $sourceSub,
                'production_number'  => $log->metadata['production_number'] ?? null,
            ];
        });

        // ─── Top produced / consumed ────────────────────────────
        $topProduced = $enrichedLogs
            ->where('type', BatchLog::TYPE_PRODUCED)
            ->sortByDesc('quantity_change_kg')->take(10)->values();

        $topConsumed = $enrichedLogs
            ->whereIn('type', [BatchLog::TYPE_DEPLETED, 'consumed'])
            ->sortByDesc(fn($r) => abs($r->quantity_change_kg))
            ->take(10)->values();

        // ─── By variant ─────────────────────────────────────────
        $batchByVariant = $enrichedLogs->groupBy('variant_id')->map(function ($items, $vid) {
            $first = $items->first();
            $produced = $items->where('type', BatchLog::TYPE_PRODUCED);
            $consumed = $items->whereIn('type', [BatchLog::TYPE_DEPLETED, 'consumed']);

            $pQty = (float) $produced->sum('quantity_change');
            $cQty = abs((float) $consumed->sum('quantity_change'));
            $pKg  = (float) $produced->sum('quantity_change_kg');
            $cKg  = abs((float) $consumed->sum('quantity_change_kg'));
            $pCost= (float) $produced->sum('total_cost');
            $cCost= (float) $consumed->sum('total_cost');

            $avgPerKg = $items->filter(fn($r) => $r->unit_cost_per_kg > 0 && $r->weight > 0);
            $avgCostPerKg = $avgPerKg->isNotEmpty()
                ? $avgPerKg->avg('unit_cost_per_kg')
                : ($items->avg('unit_cost') ?? 0);

            return (object) [
                'variant_id'         => $vid,
                'variant_name'       => $first->variant_name,
                'variant_sku'        => $first->variant_sku,
                'weight'             => $first->weight,
                'produced_count'     => $produced->count(),
                'consumed_count'     => $consumed->count(),
                'produced_quantity'  => round($pQty, 2),
                'consumed_quantity'  => round($cQty, 2),
                'net_quantity'       => round($pQty - $cQty, 2),
                'produced_kg'        => round($pKg, 2),
                'consumed_kg'        => round($cKg, 2),
                'net_kg'             => round($pKg - $cKg, 2),
                'produced_cost'      => round($pCost, 2),
                'consumed_cost'      => round($cCost, 2),
                'net_cost'           => round($pCost - $cCost, 2),
                'avg_unit_cost_per_kg' => round($avgCostPerKg, 2),
                'unique_batches'     => $items->pluck('batch_number')->filter()->unique()->count(),
            ];
        })->sortByDesc(fn($r) => abs($r->net_kg))->values();

        // ─── By month ───────────────────────────────────────────
        $batchByMonth = $enrichedLogs
            ->filter(fn($r) => $r->event_date)
            ->groupBy(fn($r) => Carbon::parse($r->event_date)->format('Y-m'))
            ->map(function ($items, $mk) {
                $produced = $items->where('type', BatchLog::TYPE_PRODUCED);
                $consumed = $items->whereIn('type', [BatchLog::TYPE_DEPLETED, 'consumed']);

                $pQty = (float) $produced->sum('quantity_change');
                $cQty = abs((float) $consumed->sum('quantity_change'));
                $pKg  = (float) $produced->sum('quantity_change_kg');
                $cKg  = abs((float) $consumed->sum('quantity_change_kg'));
                $pCost= (float) $produced->sum('total_cost');
                $cCost= (float) $consumed->sum('total_cost');

                return (object) [
                    'month'            => Carbon::parse($mk . '-01')->format('M Y'),
                    'month_key'        => $mk,
                    'produced_count'   => $produced->count(),
                    'consumed_count'   => $consumed->count(),
                    'produced_quantity'=> round($pQty, 2),
                    'consumed_quantity'=> round($cQty, 2),
                    'net_quantity'     => round($pQty - $cQty, 2),
                    'produced_kg'      => round($pKg, 2),
                    'consumed_kg'      => round($cKg, 2),
                    'net_kg'           => round($pKg - $cKg, 2),
                    'produced_cost'    => round($pCost, 2),
                    'consumed_cost'    => round($cCost, 2),
                    'net_cost'         => round($pCost - $cCost, 2),
                ];
            })->sortKeysDesc()->values();

        // ─── Batch status ───────────────────────────────────────
        $latestPerBatch = $enrichedLogs->sortByDesc('event_date')->unique(fn($r) => $r->batch_id);
        $batchStatus = [
            'active'   => $latestPerBatch->filter(fn($r) => $r->quantity_after > 0)->count(),
            'depleted' => $latestPerBatch->filter(fn($r) => $r->quantity_after <= 0)->count(),
            'expired'  => $latestPerBatch->filter(fn($r) => $r->expiry_date && Carbon::parse($r->expiry_date)->isPast())->count(),
        ];

        // ─── Paginated logs ─────────────────────────────────────
        $totalRows = $enrichedLogs->count();
        $lastPage  = max(1, (int) ceil($totalRows / $perPage));
        $page      = min($page, $lastPage);
        $offset    = ($page - 1) * $perPage;
        $paginated = $enrichedLogs->slice($offset, $perPage)->values();

        // Totals for paginated rows (footnote)
        $pgIn  = $paginated->sum(fn($b) => $b->quantity_change_kg > 0 ? $b->quantity_change_kg : 0);
        $pgOut = $paginated->sum(fn($b) => $b->quantity_change_kg < 0 ? $b->quantity_change_kg : 0);

        return response()->json([
            'success' => true,
            'data' => [
                'batchSummary'    => $batchSummary,
                'batchStatus'     => $batchStatus,
                'batchByVariant'  => $batchByVariant,
                'batchByMonth'    => $batchByMonth,
                'topProduced'     => $topProduced,
                'topConsumed'     => $topConsumed,
                'batchLogs'       => $paginated,
                'pageTotals'      => [
                    'positive_kg' => round($pgIn, 2),
                    'negative_kg' => round($pgOut, 2),
                    'total_cost'  => round($paginated->sum('total_cost'), 2),
                ],
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



}